<?php

namespace App\Http\Controllers;

use App\Models\FloodPoint;
use Illuminate\Http\Request;
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
        $query = FloodPoint::query()
            ->where(function ($q) {
                $q->whereNull('latitude')
                    ->orWhereNull('longitude')
                    ->orWhere('latitude', 0)
                    ->orWhere('longitude', 0);
            });

        if (Schema::hasColumn('flood_points', 'review_status')) {
            $query->where('review_status', 'approved');
        }

        $columns = ['id', 'cidade', 'bairro', 'logradouro', 'nivel', 'status', 'descricao', 'data_ocorrencia'];

        if (Schema::hasColumn('flood_points', 'uf')) $columns[] = 'uf';
        if (Schema::hasColumn('flood_points', 'source_url')) $columns[] = 'source_url';
        if (Schema::hasColumn('flood_points', 'confidence')) $columns[] = 'confidence';

        return $query->latest('data_ocorrencia')->take(30)->get($columns);
    }
}
