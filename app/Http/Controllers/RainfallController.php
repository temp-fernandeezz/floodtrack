<?php

namespace App\Http\Controllers;

use App\Services\RainfallService;
use Illuminate\Http\Request;

class RainfallController extends Controller
{
    public function show(Request $request, RainfallService $rainfall)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $rainfall->chuvaRecente((float) $data['lat'], (float) $data['lng']);

        if (! $result) {
            return response()->noContent();
        }

        return response()->json($result);
    }
}
