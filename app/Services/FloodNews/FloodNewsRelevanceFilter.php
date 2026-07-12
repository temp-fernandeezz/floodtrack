<?php

namespace App\Services\FloodNews;

use App\Settings\FloodTrackScraperSettings;

/**
 * Decide se uma notícia é candidata a virar um FloodPoint: se fala de alagamento urbano
 * (e não de outro tipo de ocorrência, como afogamentos ou acidentes) e se está dentro
 * dos domínios permitidos pelas configurações do scraper.
 */
class FloodNewsRelevanceFilter
{
    /** @var string[] */
    private array $keywords = [
        'alagamento', 'alagamentos', 'alagada', 'alagadas', 'alagado', 'alagados',
        'enchente', 'enchentes', 'inundação', 'inundações', 'inundado', 'inundada',
        'transbordamento', 'transborda', 'transbordou',
        'rua alagada', 'avenida alagada',
        'acúmulo de água', 'chuva causa transtornos', 'chuva forte',
    ];

    /**
     * Termos que indicam que a notícia é sobre outro assunto (morte, crime, etc.)
     * ainda que mencione alagamento de passagem.
     *
     * @var string[]
     */
    private array $blockedTerms = [
        'corpo', 'morte', 'morre', 'morreu', 'morto', 'morta',
        'afogad', 'pulou', 'queda', 'acidente', 'crime',
        'assassin', 'tiroteio', 'prisão', 'preso',
        'desaparecido', 'desaparecida', 'vítima',
        'hospital', 'resgate de pessoa',
    ];

    public function isRelevant(NewsItem $item): bool
    {
        $text = mb_strtolower($item->fullText());

        return $this->containsAny($text, $this->keywords)
            && ! $this->containsAny($text, $this->blockedTerms);
    }

    public function isAllowedBySettings(string $url, FloodTrackScraperSettings $settings): bool
    {
        $allowedDomains = $settings->allowed_domains ?? [];

        return empty($allowedDomains) || $this->hostMatchesAny($url, $allowedDomains);
    }

    public function detectSource(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        $known = [
            'g1.globo' => 'g1',
            'uol.com'  => 'uol',
            'folha.uol' => 'folha',
            'folha.com' => 'folha',
            'r7.com'   => 'r7',
            'band.uol' => 'band',
            'band.com' => 'band',
        ];

        foreach ($known as $needle => $source) {
            if (str_contains($host, $needle)) {
                return $source;
            }
        }

        return $host ?: 'unknown';
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function hostMatchesAny(string $url, array $domains): bool
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        foreach ($domains as $domain) {
            $domain = is_array($domain) ? ($domain['value'] ?? null) : $domain;

            if (! $domain) {
                continue;
            }

            $domain = strtolower($domain);

            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
