<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifica um token de resposta do Google reCAPTCHA v2 no servidor.
 *
 * Sem chave secreta configurada, a verificação é ignorada (retorna true) — mesmo padrão
 * defensivo dos outros serviços opcionais do projeto (GeocodingService, RainfallService).
 */
class RecaptchaVerifier
{
    private string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function isConfigured(): bool
    {
        return ! empty(config('services.recaptcha.secret_key'));
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isConfigured()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post($this->verifyUrl, [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Erro ao verificar reCAPTCHA: ' . $e->getMessage());

            return false;
        }

        return (bool) ($response->json('success') ?? false);
    }
}
