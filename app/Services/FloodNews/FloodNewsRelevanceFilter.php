<?php

namespace App\Services\FloodNews;

use App\Settings\FloodTrackScraperSettings;

/**
 * Decide se uma notícia é candidata a virar um FloodPoint: se fala de alagamento urbano
 * (e não de outro tipo de ocorrência, como afogamentos ou acidentes) e se está dentro
 * dos domínios/estados permitidos pelas configurações do scraper.
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

        if (! empty($allowedDomains) && ! $this->hostMatchesAny($url, $allowedDomains)) {
            return false;
        }

        $states = array_values(array_filter(
            $settings->observed_states ?? [],
            fn ($s) => ($s['enabled'] ?? true) === true
        ));

        // Sem estados configurados → aceita tudo
        if (empty($states)) {
            return true;
        }

        foreach ($states as $state) {
            $allowed = $state['allowed_patterns'] ?? [];
            $deny    = $state['deny_patterns'] ?? [];

            if ($this->urlMatchesAnyPattern($url, $deny)) {
                continue;
            }

            // Se não há padrões permitidos, este estado aceita qualquer URL não bloqueada
            if (empty($allowed) || $this->urlMatchesAnyPattern($url, $allowed)) {
                return true;
            }
        }

        return false;
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

    private function urlMatchesAnyPattern(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = is_array($pattern) ? ($pattern['pattern'] ?? ($pattern['value'] ?? null)) : $pattern;

            if ($pattern && str_contains($url, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
