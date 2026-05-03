<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Settings\FloodTrackScraperSettings;

class FloodTrackScraperSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $ufs = [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];

        /** @var FloodTrackScraperSettings $settings */
        $settings = app(FloodTrackScraperSettings::class);

        $settings->allowed_domains = ['g1.globo.com'];

        $settings->observed_states = collect($ufs)->map(function (string $name, string $uf) {

            $ufLower = strtolower($uf);

            $allowedPatterns = [
                "/{$ufLower}/",
            ];

            // 🔥 Especial para São Paulo
            if ($uf === 'SP') {
                $allowedPatterns = [
                    '/sp/',
                    '/sp/sao-paulo/',
                    '/sp/campinas-regiao/',
                    '/sp/vale-do-paraiba-regiao/',
                    '/sp/santos-regiao/',
                    '/sp/sorocaba-jundiai/',
                    '/sp/ribeirao-preto-franca/',
                    '/sp/presidente-prudente-regiao/',
                    '/sp/bauru-marilia/',
                ];
            }

            return [
                'enabled' => true,
                'state' => $uf,
                'label' => $name,
                'allowed_patterns' => $allowedPatterns,
                'deny_patterns' => [],
            ];
        })->values()->toArray();

        $settings->save();
    }
}
