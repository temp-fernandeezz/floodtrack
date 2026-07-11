<?php

namespace App\Http\Controllers;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use App\Services\FloodRiskScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FloodPointPublicController extends Controller
{
    public function index(Request $request)
    {
        $query = FloodPoint::query();

        // Só aprovados
        if (Schema::hasColumn('flood_points', 'review_status')) {
            $query->where('review_status', 'approved');
        }

        // filtros simples
        if ($request->filled('cidade')) {
            $query->where('cidade', 'like', '%' . $request->cidade . '%');
        }
        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $points = $query->latest('data_ocorrencia')->paginate(10)->withQueryString();

        return view('pages.home', compact('points'));
    }

    public function stats(FloodRiskScoreService $riskScore)
    {
        $rankingRisco = $riskScore->rankingPorCidade();

        $total     = FloodPoint::count();
        $comCoords = FloodPoint::whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->count();
        $ativos    = FloodPoint::where('status', 'ativo')->count();
        $noticias  = NewsArticle::count();

        $porNivel = FloodPoint::selectRaw('nivel, count(*) as total')
            ->groupBy('nivel')
            ->orderByDesc('total')
            ->pluck('total', 'nivel');

        $porUf = FloodPoint::selectRaw('uf, count(*) as total')
            ->whereNotNull('uf')
            ->groupBy('uf')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $porCidade = FloodPoint::selectRaw('cidade, uf, count(*) as total')
            ->groupBy('cidade', 'uf')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $primeira  = FloodPoint::min('data_ocorrencia');
        $ultima    = FloodPoint::max('data_ocorrencia');

        $porDia = FloodPoint::selectRaw('DATE(data_ocorrencia) as dia, count(*) as total')
            ->whereNotNull('data_ocorrencia')
            ->where('data_ocorrencia', '>=', now()->subDays(29))
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        return view('pages.stats', compact(
            'total', 'comCoords', 'ativos', 'noticias',
            'porNivel', 'porUf', 'porCidade',
            'primeira', 'ultima', 'porDia', 'rankingRisco'
        ));
    }

    public function show(FloodPoint $floodPoint)
    {
        if (Schema::hasColumn('flood_points', 'review_status') && $floodPoint->review_status !== 'approved') {
            abort(404);
        }

        return view('public.show', compact('floodPoint'));
    }

    public function api(Request $request)
    {
        $query = FloodPoint::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        if (Schema::hasColumn('flood_points', 'review_status')) {
            $query->where('review_status', 'approved');
        }

        if ($request->filled('cidade')) {
            $query->where('cidade', 'like', '%' . $request->cidade . '%');
        }
        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }

        // Sem filtro explícito de status, o mapa "ao vivo" mostra só ocorrências ativas —
        // pontos expirados (ver flood:expire-points) continuam existindo pra exportação,
        // mas não poluem o mapa como se fossem atuais.
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        } elseif (! $request->filled('status')) {
            $query->where('status', 'ativo');
        }

        $columns = ['id', 'cidade', 'bairro', 'logradouro', 'latitude', 'longitude', 'nivel', 'status', 'descricao', 'data_ocorrencia'];

        if (Schema::hasColumn('flood_points', 'uf')) {
            $columns[] = 'uf';
        }

        return $query->latest('data_ocorrencia')->take(500)->get($columns);
    }

    public function apiPending(Request $request)
    {
        // Mostra todos os pontos sem coordenadas, aprovados ou pendentes de revisão
        $query = FloodPoint::query()
            ->where(function ($q) {
                $q->whereNull('latitude')
                    ->orWhereNull('longitude')
                    ->orWhere('latitude', 0)
                    ->orWhere('longitude', 0);
            });

        $columns = ['id', 'cidade', 'bairro', 'logradouro', 'nivel', 'status', 'descricao', 'data_ocorrencia'];

        if (Schema::hasColumn('flood_points', 'uf')) $columns[] = 'uf';
        if (Schema::hasColumn('flood_points', 'source_url')) $columns[] = 'source_url';
        if (Schema::hasColumn('flood_points', 'confidence')) $columns[] = 'confidence';

        return $query->latest('data_ocorrencia')->take(30)->get($columns);
    }

    /**
     * Exporta o histórico completo de ocorrências (inclusive expiradas/resolvidas) em CSV.
     * Diferente do mapa "ao vivo", aqui o padrão é NÃO filtrar por status.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = FloodPoint::query();

        if (Schema::hasColumn('flood_points', 'review_status')) {
            $query->where('review_status', 'approved');
        }

        if ($request->filled('cidade')) {
            $query->where('cidade', 'like', '%' . $request->cidade . '%');
        }
        if ($request->filled('uf')) {
            $query->where('uf', strtoupper($request->uf));
        }
        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_ocorrencia', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_ocorrencia', '<=', $request->data_fim);
        }

        $filename = 'floodtrack-ocorrencias-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'ID', 'Cidade', 'UF', 'Bairro', 'Logradouro', 'Latitude', 'Longitude',
                'Nível', 'Status', 'Descrição', 'Data Ocorrência', 'Fontes Consolidadas',
                'Tipo de Fonte', 'URL da Fonte', 'Confiança', 'Criado em',
            ]);

            $query->orderBy('data_ocorrencia')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    fputcsv($out, [
                        $p->id, $p->cidade, $p->uf, $p->bairro, $p->logradouro,
                        $p->latitude, $p->longitude, $p->nivel, $p->status, $p->descricao,
                        $p->data_ocorrencia, $p->merged_sources_count ?? 1,
                        $p->source_type, $p->source_url, $p->confidence, $p->created_at,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Exporta o ranking de risco histórico por cidade em CSV.
     */
    public function exportRisk(FloodRiskScoreService $riskScore): StreamedResponse
    {
        $ranking  = $riskScore->rankingPorCidade(limit: 1000);
        $filename = 'floodtrack-ranking-risco-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($ranking) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Cidade', 'UF', 'Ocorrências (90 dias)', 'Pontuação (0-100)', 'Nível de Risco']);

            foreach ($ranking as $row) {
                fputcsv($out, [$row->cidade, $row->uf, $row->ocorrencias, $row->score, $row->nivel_risco]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
