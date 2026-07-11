<?php

namespace App\Http\Controllers;

use App\Models\FloodPoint;
use App\Services\GeocodingService;
use App\Services\RecaptchaVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FloodReportController extends Controller
{
    public function create()
    {
        return view('pages.report');
    }

    public function store(Request $request, GeocodingService $geocoding, RecaptchaVerifier $recaptcha)
    {
        // Honeypot: campo invisível pra humanos. Se veio preenchido, é bot — finge sucesso e descarta.
        if (filled($request->input('website'))) {
            return redirect()->route('flood-points.report.create')->with('reported', true);
        }

        if (! $recaptcha->verify($request->input('g-recaptcha-response'), $request->ip())) {
            return back()->withInput()->withErrors(['descricao' => 'Não foi possível confirmar que você não é um robô. Tente novamente.']);
        }

        $data = $request->validate([
            'cidade'      => ['required', 'string', 'max:255'],
            'uf'          => ['nullable', 'string', 'size:2'],
            'bairro'      => ['nullable', 'string', 'max:255'],
            'logradouro'  => ['nullable', 'string', 'max:255'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
            'nivel'       => ['required', Rule::in(['baixo', 'medio', 'alto'])],
            'descricao'   => ['required', 'string', 'max:500'],
        ]);

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        if (! $lat || ! $lng) {
            $address = collect([$data['bairro'] ?? null, $data['cidade'], $data['uf'] ?? null, 'Brasil'])
                ->filter()
                ->implode(', ');

            $geo = $geocoding->search($address);
            $lat = $geo['latitude'] ?? null;
            $lng = $geo['longitude'] ?? null;
        }

        FloodPoint::create([
            'cidade'          => $data['cidade'],
            'uf'              => isset($data['uf']) ? strtoupper($data['uf']) : null,
            'bairro'          => $data['bairro'] ?? null,
            'logradouro'      => $data['logradouro'] ?? null,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'nivel'           => $data['nivel'],
            'status'          => 'ativo',
            'descricao'       => $data['descricao'],
            'data_ocorrencia' => now(),
            'source_type'     => 'citizen',
            'source_url'      => null,
            'review_status'   => 'pending',
            'confidence'      => 50,
            'reporter_ip'     => $request->ip(),
        ]);

        return redirect()->route('flood-points.report.create')->with('reported', true);
    }
}
