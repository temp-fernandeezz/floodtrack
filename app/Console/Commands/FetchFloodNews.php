<?php

namespace App\Console\Commands;

use App\Models\FloodPoint;
use App\Models\NewsArticle;
use App\Services\GeocodingService;
use App\Settings\FloodTrackScraperSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchFloodNews extends Command
{
    protected $signature = 'flood:fetch-news';

    protected $description = 'Busca noticias RSS sobre alagamentos e cria pontos sugeridos';

    public function handle(
        FloodTrackScraperSettings $scraperSettings,
        GeocodingService $geocodingService
    ) {
        $rssUrls = $scraperSettings->rss_urls ?: [
            'https://g1.globo.com/rss/g1/',
        ];

        $keywords = [
            'alagamento', 'alagamentos', 'alagada', 'alagadas', 'alagado', 'alagados',
            'enchente', 'enchentes', 'inundação', 'inundações', 'inundado', 'inundada',
            'transbordamento', 'transborda', 'transbordou',
            'rua alagada', 'avenida alagada',
            'acúmulo de água', 'chuva causa transtornos', 'chuva forte',
        ];

        $imported = 0;

        foreach ($rssUrls as $rssUrl) {
            $this->info("Buscando RSS: {$rssUrl}");

            $response = Http::timeout(15)->get($rssUrl);

            if (! $response->ok()) {
                $this->warn("Erro ao buscar RSS: {$rssUrl}");
                continue;
            }

            $xml = @simplexml_load_string($response->body());

            if (! $xml || ! isset($xml->channel->item)) {
                $this->warn("RSS inválido ou sem itens: {$rssUrl}");
                continue;
            }

            foreach ($xml->channel->item as $item) {
                $title = (string) $item->title;
                $link  = (string) $item->link;
                $desc  = strip_tags((string) $item->description);
                $pub   = isset($item->pubDate) ? new \DateTime((string) $item->pubDate) : null;

                $fullText = $title . ' ' . $desc;

                // 1. Filtros rápidos (antes de qualquer I/O)
                if (! $this->isAllowedBySettings($link, $scraperSettings)) {
                    continue;
                }

                if (! $this->containsAny(mb_strtolower($fullText), $keywords)) {
                    continue;
                }

                if ($this->isIrrelevantNews($fullText)) {
                    $this->warn("Ignorado por não ser ocorrência urbana de alagamento: {$title}");
                    continue;
                }

                // 2. Deduplicação — salva o artigo ANTES de tentar geocoding
                //    Isso evita que o mesmo item seja retentado infinitamente quando geocoding falha.
                if (NewsArticle::where('url', $link)->exists()) {
                    continue;
                }

                $article = NewsArticle::create([
                    'source'       => $this->detectSource($link),
                    'url'          => $link,
                    'title'        => $title,
                    'summary'      => $desc,
                    'published_at' => $pub?->format('Y-m-d H:i:s'),
                    'raw'          => json_encode($item),
                ]);

                // 3. Extração de localização
                [$bairro, $cidade, $uf, $nivel, $confidence] = $this->extractLocationAndSeverity(
                    title: $title,
                    summary: $desc,
                    url: $link
                );

                $cidade = $this->cleanLocationName($cidade);
                $bairro = $this->cleanLocationName($bairro);
                $uf     = $uf ?? null;

                if (! $cidade || $this->isGenericLocation($cidade)) {
                    $this->warn("Sem cidade identificável: {$title}");
                    // Cria ponto sem coordenadas para revisão manual
                    FloodPoint::create([
                        'cidade'          => $cidade ?? 'Não identificada',
                        'uf'              => $uf,
                        'bairro'          => $bairro,
                        'logradouro'      => null,
                        'latitude'        => null,
                        'longitude'       => null,
                        'nivel'           => $nivel ?? 'medio',
                        'status'          => 'ativo',
                        'descricao'       => $title,
                        'data_ocorrencia' => $article->published_at,
                        'source_type'     => 'news',
                        'source_url'      => $link,
                        'review_status'   => 'pending',
                        'confidence'      => $confidence,
                    ]);
                    continue;
                }

                // 4. Geocoding — tenta com bairro, depois só cidade
                $address = collect([$bairro, $cidade, $uf, 'Brasil'])->filter()->implode(', ');
                $geo     = $geocodingService->search($address);

                if (! $geo && $bairro) {
                    $fallback = collect([$cidade, $uf, 'Brasil'])->filter()->implode(', ');
                    $geo      = $geocodingService->search($fallback);
                }

                if (! $geo) {
                    Log::warning("Geocoding não encontrado para: {$address}");
                    $this->warn("Sem coordenadas (ponto pendente): {$address}");

                    // Salva sem coordenadas — aparece nos cards de pendentes
                    FloodPoint::create([
                        'cidade'          => $cidade,
                        'uf'              => $uf,
                        'bairro'          => $bairro,
                        'logradouro'      => null,
                        'latitude'        => null,
                        'longitude'       => null,
                        'nivel'           => $nivel ?? 'medio',
                        'status'          => 'ativo',
                        'descricao'       => $title,
                        'data_ocorrencia' => $article->published_at,
                        'source_type'     => 'news',
                        'source_url'      => $link,
                        'review_status'   => 'pending',
                        'confidence'      => $confidence,
                    ]);
                    continue;
                }

                // 5. Geocoding OK → ponto aprovado aparece no mapa
                FloodPoint::create([
                    'cidade'          => $cidade,
                    'uf'              => $uf,
                    'bairro'          => $bairro,
                    'logradouro'      => null,
                    'latitude'        => $geo['latitude'],
                    'longitude'       => $geo['longitude'],
                    'nivel'           => $nivel ?? 'medio',
                    'status'          => 'ativo',
                    'descricao'       => $title,
                    'data_ocorrencia' => $article->published_at,
                    'source_type'     => 'news',
                    'source_url'      => $link,
                    'review_status'   => 'approved',
                    'confidence'      => $confidence,
                ]);

                $imported++;
                $this->info("Importado: {$title}");
            }
        }

        $this->info("Concluído! {$imported} notícia(s) com coordenadas importadas.");

        return self::SUCCESS;
    }

    private function detectSource(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (str_contains($host, 'g1.globo')) return 'g1';
        if (str_contains($host, 'uol.com')) return 'uol';
        if (str_contains($host, 'folha.uol') || str_contains($host, 'folha.com')) return 'folha';
        if (str_contains($host, 'r7.com')) return 'r7';
        if (str_contains($host, 'band.uol') || str_contains($host, 'band.com')) return 'band';
        return parse_url($url, PHP_URL_HOST) ?? 'unknown';
    }

    private function isAllowedBySettings(string $url, FloodTrackScraperSettings $settings): bool
    {
        $allowedDomains = $settings->allowed_domains ?? [];

        if (! empty($allowedDomains)) {
            $host    = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
            $okDomain = false;

            foreach ($allowedDomains as $domain) {
                $domain = is_array($domain) ? ($domain['value'] ?? null) : $domain;
                if (! $domain) continue;
                $domain = strtolower($domain);
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    $okDomain = true;
                    break;
                }
            }

            if (! $okDomain) return false;
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

            // Verifica se a URL está na lista de bloqueados deste estado
            $isDenied = false;
            foreach ($deny as $pattern) {
                $pattern = is_array($pattern) ? ($pattern['pattern'] ?? null) : $pattern;
                if ($pattern && str_contains($url, $pattern)) {
                    $isDenied = true;
                    break;
                }
            }

            if ($isDenied) continue;

            // Se não há padrões permitidos, este estado aceita qualquer URL não bloqueada
            if (empty($allowed)) {
                return true;
            }

            foreach ($allowed as $pattern) {
                $pattern = is_array($pattern) ? ($pattern['pattern'] ?? null) : $pattern;
                if ($pattern && str_contains($url, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsAny(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, mb_strtolower($kw))) return true;
        }
        return false;
    }

    private function isIrrelevantNews(string $text): bool
    {
        $text = mb_strtolower($text);

        $blockedTerms = [
            'corpo', 'morte', 'morre', 'morreu', 'morto', 'morta',
            'afogad', 'pulou', 'queda', 'acidente', 'crime',
            'assassin', 'tiroteio', 'prisão', 'preso',
            'desaparecido', 'desaparecida', 'vítima',
            'hospital', 'resgate de pessoa',
        ];

        foreach ($blockedTerms as $term) {
            if (str_contains($text, $term)) return true;
        }
        return false;
    }

    private function extractLocationAndSeverity(string $title, string $summary, string $url): array
    {
        $text  = trim($title . ' ' . $summary);
        $lower = mb_strtolower($text);

        // Nível de severidade
        $nivel      = 'medio';
        $confidence = 40;

        if (str_contains($lower, 'estado de emergência') || str_contains($lower, 'transbord')) {
            $nivel      = 'alto';
            $confidence = 80;
        } elseif (str_contains($lower, 'alagamento') || str_contains($lower, 'enchente') || str_contains($lower, 'inundação')) {
            $nivel      = 'medio';
            $confidence = 65;
        } elseif (str_contains($lower, 'acúmulo de água')) {
            $nivel      = 'baixo';
            $confidence = 55;
        }

        // UF: primeiro da URL, depois do texto
        $uf = $this->inferUfFromG1Url($url) ?? $this->inferUfFromText($text);
        if ($uf) $confidence += 10;

        // Cidade e bairro: múltiplas estratégias
        [$bairro, $cidade] = $this->inferBairroCidadeFromText($title);

        if (! $cidade) {
            [$bairroSum, $cidadeSum] = $this->inferBairroCidadeFromText($summary);
            $bairro = $bairro ?: $bairroSum;
            $cidade = $cidade ?: $cidadeSum;
        }

        if (! $cidade) {
            $cidade = $this->inferCityFromG1Url($url);
        }

        if ($cidade) $confidence += 15;
        if ($bairro) $confidence += 10;

        return [$bairro, $cidade, $uf, $nivel, min(100, $confidence)];
    }

    private function inferUfFromG1Url(string $url): ?string
    {
        $path     = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_values(array_filter(explode('/', $path)));
        $uf       = strtoupper($segments[0] ?? '');
        return preg_match('/^[A-Z]{2}$/', $uf) ? $uf : null;
    }

    private function inferUfFromText(string $text): ?string
    {
        $ufs = 'AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO';

        // "em Cidade - SP" ou "em Cidade (SP)"
        if (preg_match('/[-–\(]\s*(' . $ufs . ')\b/u', $text, $m)) {
            return $m[1];
        }

        // UF isolada no texto ex. "... SP ..."
        if (preg_match('/\b(' . $ufs . ')\b/', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private function inferCityFromG1Url(string $url): ?string
    {
        $path     = parse_url($url, PHP_URL_PATH) ?? '';
        $segments = array_values(array_filter(explode('/', $path)));

        $ignored = [
            'ac','al','ap','am','ba','ce','df','es','go','ma','mt','ms','mg',
            'pa','pb','pr','pe','pi','rj','rn','rs','ro','rr','sc','sp','se','to',
            'noticia','rss','g1','index.html',
        ];

        foreach ($segments as $segment) {
            $segment = strtolower($segment);
            if (in_array($segment, $ignored, true)) continue;
            if (str_contains($segment, 'regiao')) continue;
            if (str_contains($segment, 'noticia')) continue;
            if (preg_match('/^\d/', $segment)) continue; // começa com número = slug de notícia

            $city = mb_convert_case(str_replace('-', ' ', $segment), MB_CASE_TITLE, 'UTF-8');
            if (mb_strlen(trim($city)) >= 3) return trim($city);
        }

        return null;
    }

    /**
     * Extrai bairro e cidade do texto usando múltiplos padrões.
     */
    private function inferBairroCidadeFromText(string $text): array
    {
        $bairro = null;
        $cidade = null;

        // Padrão: "no/na/em [bairro], em [cidade]"
        if (preg_match('/\b(?:no|na|em)\s+(.+?),\s+(?:em|na cidade de)\s+([^,\.;\-]+)/iu', $text, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // Padrão: "em [bairro], em [cidade]" (sem "no/na" inicial)
        if (preg_match('/\bem\s+([^,]+),\s+em\s+([^,\.;\-]+)/iu', $text, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // Padrão: "em [Cidade] - UF" ou "em [Cidade]/UF"
        if (preg_match('/\bem\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]+?)\s*[-\/]\s*[A-Z]{2}\b/u', $text, $m)) {
            $cidade = trim($m[1]);
            return [$bairro, $cidade];
        }

        // Padrão: ", em [Cidade]" — última ocorrência
        if (preg_match_all('/,\s*em\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]{2,40}?)(?=[,\.\;]|$)/iu', $text, $m) && ! empty($m[1])) {
            $cidade = trim(end($m[1]));
            return [$bairro, $cidade];
        }

        // Padrão: "em [Cidade]" — última ocorrência no texto
        if (preg_match_all('/\bem\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]{2,40}?)(?=[,\.\;]|$)/iu', $text, $m) && ! empty($m[1])) {
            $cidade = trim(end($m[1]));
            return [$bairro, $cidade];
        }

        return [$bairro, $cidade];
    }

    private function cleanLocationName(?string $value): ?string
    {
        if (! $value) return null;

        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = preg_replace('/\b(VÍDEOS|VIDEOS|Imagens cedidas|Veja mais|G1|SP1|SP2|Reprodução|Assista)\b.*$/iu', '', $value);
        $value = trim($value, " \t\n\r\0\x0B.,;-");

        if ($value === '' || mb_strlen($value) < 2) return null;

        $invalidTerms = [
            'reprodução', 'assista', 'vídeo', 'videos', 'vídeos',
            'moradores protestam', 'infraestrutura', 'diversos bairros',
            'pontilhões', 'pontes submersas', 'mananciais', 'mercado',
        ];

        foreach ($invalidTerms as $term) {
            if (str_contains(mb_strtolower($value), mb_strtolower($term))) return null;
        }

        return $value;
    }

    private function isGenericLocation(?string $value): bool
    {
        if (! $value) return true;

        $value = mb_strtolower($value);

        $genericTerms = [
            'áreas mais vulneráveis', 'região', 'vale e região', 'cidade', 'cidades',
            'estado', 'capital', 'interior', 'litoral', 'chuva', 'frente fria',
            'tempo', 'vento', 'bairros', 'ruas', 'avenidas', 'não identificada',
        ];

        foreach ($genericTerms as $term) {
            if (str_contains($value, $term)) return true;
        }

        return false;
    }
}
