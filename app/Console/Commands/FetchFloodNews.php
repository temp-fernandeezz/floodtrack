<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\NewsArticle;
use App\Models\FloodPoint;
use App\Settings\FloodTrackScraperSettings;

class FetchFloodNews extends Command
{
    protected $signature = 'flood:fetch-news';
    protected $description = 'Busca noticias do G1 sobre alagamentos e cria pontos sugeridos';

    public function handle(FloodTrackScraperSettings $scraperSettings)
    {
        $rssUrl = 'https://g1.globo.com/rss/g1/sp'; // feed geral SP
        $keywords = ['alagamento', 'alagamentos', 'enchente', 'enchentes', 'inundação', 'chuva forte', 'deslizamento'];

        $this->info('Buscando RSS do G1...');

        $response = Http::timeout(15)->get($rssUrl);

        if (! $response->ok()) {
            $this->error('Erro ao buscar RSS do G1');
            return self::FAILURE;
        }

        $xml = @simplexml_load_string($response->body());

        if (! $xml || ! isset($xml->channel->item)) {
            $this->error('RSS inválido ou sem itens');
            return self::FAILURE;
        }

        $imported = 0;

        foreach ($xml->channel->item as $item) {
            $title = (string) $item->title;
            $link  = (string) $item->link;
            $desc  = strip_tags((string) $item->description);
            $pub   = isset($item->pubDate) ? new \DateTime((string) $item->pubDate) : null;

            if (! $this->isAllowedBySettings($link, $scraperSettings)) {
                continue;
            }

            // Filtro por palavras-chave
            $haystack = mb_strtolower($title . ' ' . $desc);
            if (! $this->containsAny($haystack, $keywords)) {
                continue;
            }

            // Deduplicar por URL
            if (NewsArticle::where('url', $link)->exists()) {
                continue;
            }

            // Salva notícia
            $article = NewsArticle::create([
                'source' => 'g1',
                'url' => $link,
                'title' => $title,
                'summary' => $desc,
                'published_at' => $pub?->format('Y-m-d H:i:s'),
                'raw' => json_encode($item),
            ]);

            // Extração simples (MVP)
            [$bairro, $cidade, $uf, $nivel, $confidence] = $this->extractLocationAndSeverity(
                title: $title,
                summary: $desc,
                url: $link
            );

            FloodPoint::create([
                'cidade' => $cidade ?? 'Indefinido',
                'uf' => $uf ?? 'SP', // fallback (rss é SP)
                'bairro' => $bairro,
                'logradouro' => null,

                'latitude' => null,
                'longitude' => null,

                'nivel' => $nivel ?? 'medio',
                'status' => 'ativo',
                'descricao' => $title,
                'data_ocorrencia' => $article->published_at,

                'source_type' => 'news',
                'source_url' => $link,
                'review_status' => 'pending',
                'confidence' => $confidence,
            ]);

            $imported++;
            $this->info("Importado: {$title}");
        }

        $this->info("Concluído! {$imported} notícia(s) importadas.");
        return self::SUCCESS;
    }

    private function isAllowedBySettings(string $url, FloodTrackScraperSettings $settings): bool
    {
        // 1) Domínio
        $allowedDomains = $settings->allowed_domains ?? [];
        if (! empty($allowedDomains)) {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

            $okDomain = false;
            foreach ($allowedDomains as $domain) {
                $domain = strtolower($domain);
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    $okDomain = true;
                    break;
                }
            }

            if (! $okDomain) {
                return false;
            }
        }

        // 2) Patterns por estado (ativos)
        $states = array_values(array_filter($settings->observed_states ?? [], fn ($s) => ($s['enabled'] ?? true) === true));

        if (empty($states)) {
            return true;
        }

        foreach ($states as $state) {
            $allowed = $state['allowed_patterns'] ?? [];
            $deny = $state['deny_patterns'] ?? [];

            // deny tem prioridade
            foreach ($deny as $pattern) {
                if ($pattern && str_contains($url, $pattern)) {
                    continue 2;
                }
            }

            // se tiver allow, precisa bater em algum
            if (! empty($allowed)) {
                foreach ($allowed as $pattern) {
                    if ($pattern && str_contains($url, $pattern)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, mb_strtolower($kw))) {
                return true;
            }
        }
        return false;
    }

    private function extractLocationAndSeverity(string $title, string $summary, string $url): array
    {
        $text = trim($title . ' ' . $summary);
        $lower = mb_strtolower($text);

        // Severidade
        $nivel = 'medio';
        $confidence = 40;

        if (str_contains($lower, 'desabrigad') || str_contains($lower, 'estado de emergência') || str_contains($lower, 'transbord')) {
            $nivel = 'alto';
            $confidence = 80;
        } elseif (str_contains($lower, 'alagamento') || str_contains($lower, 'enchente') || str_contains($lower, 'deslizamento')) {
            $nivel = 'medio';
            $confidence = 65;
        } elseif (str_contains($lower, 'acúmulo de água')) {
            $nivel = 'baixo';
            $confidence = 55;
        }

        // UF (pelo link: /sp/, /rj/, /mg/...) - no rss SP, tende a ser SP
        $uf = $this->inferUfFromG1Url($url);
        if ($uf) $confidence += 10;

        // Bairro + Cidade
        [$bairro, $cidade] = $this->inferBairroCidadeFromText($title);

        // Fallback
        if (! $cidade) {
            if (preg_match_all('/\bem\s+([A-Za-zÀ-ÖØ-öø-ÿ]+(?:\s+[A-Za-zÀ-ÖØ-öø-ÿ]+)*)\b/u', $text, $all) && !empty($all[1])) {
                $cidade = trim(end($all[1]));
            }
        }

        if ($cidade) $confidence += 15;
        if ($bairro) $confidence += 10;

        return [$bairro, $cidade, $uf, $nivel, min(100, $confidence)];
    }

    private function inferUfFromG1Url(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_values(array_filter(explode('/', $path)));

        $uf = $segments[0] ?? null;
        if (! $uf) return null;

        $uf = strtoupper($uf);

        if (preg_match('/^[A-Z]{2}$/', $uf)) return $uf;

        return null;
    }

    private function inferBairroCidadeFromText(string $title): array
    {
        $bairro = null;
        $cidade = null;

        if (preg_match('/\b(?:no|na|em)\s+([^,]+),\s+em\s+([^,]+)\b/iu', $title, $m)) {
            $bairro = trim($m[1]);
            $cidade = trim($m[2]);
            return [$bairro, $cidade];
        }

        if (preg_match_all('/,\s+em\s+([^,]+)\b/iu', $title, $m) && !empty($m[1])) {
            $cidade = trim(end($m[1]));
        }

        return [$bairro, $cidade];
    }
}
