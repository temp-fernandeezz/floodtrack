<?php

namespace App\Http\Controllers;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function stats()
    {
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
            'primeira', 'ultima', 'porDia'
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
}
