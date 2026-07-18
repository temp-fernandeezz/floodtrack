<?php

namespace App\Services\FloodNews;

use App\Services\LocationExtractorService;

/**
 * Extrai cidade, bairro, UF e nível de severidade do texto de uma notícia.
 *
 * Estratégia:
 *  1. IA (Claude Haiku via LocationExtractorService) — mais precisa, mas pode falhar/estar desligada.
 *  2. Fallback por regex clássico sobre título/resumo e pela URL (útil para feeds regionais do G1).
 *
 * O resultado já vem "limpo" (nomes genéricos e ruído de texto descartados), então o
 * chamador só precisa checar ExtractedLocation::hasIdentifiableCity().
 */
class FloodLocationParser
{
    public function __construct(
        private readonly LocationExtractorService $locationExtractor,
    ) {
    }

    public function extract(NewsItem $item): ExtractedLocation
    {
        $text  = $item->fullText();
        $lower = mb_strtolower($text);

        [$nivel, $confidence] = $this->baseSeverity($lower);

        // UF da URL é muito confiável para feeds do G1 — extraímos antes da IA
        $ufFromUrl = $this->inferUfFromG1Url($item->link);

        $ai = $this->locationExtractor->extract($item->title, $item->summary);

        // A IA lê o artigo inteiro e consegue distinguir um relato de alagamento real
        // de uma notícia que só menciona a palavra de passagem (contexto histórico,
        // outro assunto). Se ela disser que não é um caso real, não vale a pena criar
        // um ponto — mesmo que o filtro de palavras-chave (mais grosseiro) tenha deixado passar.
        if ($ai && $ai['ocorrencia_real'] === false) {
            return new ExtractedLocation(null, null, $ufFromUrl ?? $ai['uf'] ?? null, $nivel, 0, ocorrenciaReal: false);
        }

        if ($ai && $ai['cidade']) {
            $cidade = $ai['cidade'];
            $bairro = $ai['bairro'];
            // UF da URL tem prioridade — é mais confiável que a IA para feeds regionais
            $uf     = $ufFromUrl ?? $ai['uf'] ?? $this->inferUfFromText($text);
            $nivel  = $ai['nivel'] ?? $nivel;

            $confidence = min(100, $confidence + 30 + ($bairro ? 10 : 0));

            return $this->finalize($bairro, $cidade, $uf, $nivel, $confidence);
        }

        // --- Fallback: regex clássico ---
        $uf = $ufFromUrl ?? $this->inferUfFromText($text);
        if ($uf) {
            $confidence += 10;
        }

        [$bairro, $cidade] = $this->inferBairroCidadeFromText($item->title);

        if (! $cidade) {
            [$bairroSum, $cidadeSum] = $this->inferBairroCidadeFromText($item->summary);
            $bairro = $bairro ?: $bairroSum;
            $cidade = $cidade ?: $cidadeSum;
        }

        if (! $cidade) {
            $cidade = $this->inferCityFromG1Url($item->link);
        }

        if ($cidade) {
            $confidence += 15;
        }
        if ($bairro) {
            $confidence += 10;
        }

        return $this->finalize($bairro, $cidade, $uf, $nivel, min(100, $confidence));
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function baseSeverity(string $lower): array
    {
        if (str_contains($lower, 'estado de emergência') || str_contains($lower, 'transbord')) {
            return ['alto', 80];
        }

        if (str_contains($lower, 'alagamento') || str_contains($lower, 'enchente') || str_contains($lower, 'inundação')) {
            return ['medio', 65];
        }

        if (str_contains($lower, 'acúmulo de água')) {
            return ['baixo', 55];
        }

        return ['medio', 40];
    }

    private function finalize(?string $bairro, ?string $cidade, ?string $uf, string $nivel, int $confidence): ExtractedLocation
    {
        $cidade = $this->cleanLocationName($cidade);
        $bairro = $this->cleanLocationName($bairro);

        if ($cidade !== null && ($this->isGenericLocation($cidade) || $this->looksLikeDescription($cidade))) {
            $cidade = null;
        }

        if ($bairro !== null && ($this->isGenericLocation($bairro) || $this->looksLikeDescription($bairro))) {
            $bairro = null;
        }

        return new ExtractedLocation($bairro, $cidade, $uf, $nivel, $confidence);
    }

    /**
     * Um nome de lugar de verdade é curto e não tem vírgula interna. Textos longos
     * ou com vírgula quase sempre são um trecho de frase que a extração pegou por
     * engano (ex.: "grande volume de chuva em áreas urbanas, agravado pela..."),
     * não um bairro ou cidade real.
     */
    private function looksLikeDescription(string $value): bool
    {
        if (mb_strlen($value) > 60 || substr_count($value, ',') > 0) {
            return true;
        }

        $descriptionWords = [
            'sistemas', 'drenagem', 'obstrução', 'volume', 'casos',
            'municípios', 'posição', 'consecutivo', 'pontuação',
        ];

        $lower = mb_strtolower($value);

        foreach ($descriptionWords as $word) {
            if (str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
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
            'ac', 'al', 'ap', 'am', 'ba', 'ce', 'df', 'es', 'go', 'ma', 'mt', 'ms', 'mg',
            'pa', 'pb', 'pr', 'pe', 'pi', 'rj', 'rn', 'rs', 'ro', 'rr', 'sc', 'sp', 'se', 'to',
            'noticia', 'rss', 'g1', 'index.html',
        ];

        foreach ($segments as $segment) {
            $segment = strtolower($segment);

            if (in_array($segment, $ignored, true)) {
                continue;
            }
            if (str_contains($segment, 'regiao') || str_contains($segment, 'noticia')) {
                continue;
            }
            if (preg_match('/^\d/', $segment)) {
                continue; // começa com número = slug de notícia
            }

            $city = mb_convert_case(str_replace('-', ' ', $segment), MB_CASE_TITLE, 'UTF-8');

            if (mb_strlen(trim($city)) >= 3) {
                return trim($city);
            }
        }

        return null;
    }

    /**
     * Extrai bairro e cidade do texto usando múltiplos padrões.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function inferBairroCidadeFromText(string $text): array
    {
        // "no/na/em [bairro], em [cidade]"
        if (preg_match('/\b(?:no|na|em)\s+(.+?),\s+(?:em|na cidade de)\s+([^,\.;\-]+)/iu', $text, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // "em [bairro], em [cidade]" (sem "no/na" inicial)
        if (preg_match('/\bem\s+([^,]+),\s+em\s+([^,\.;\-]+)/iu', $text, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        // "em [Cidade] - UF" ou "em [Cidade]/UF"
        if (preg_match('/\bem\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]+?)\s*[-\/]\s*[A-Z]{2}\b/u', $text, $m)) {
            return [null, trim($m[1])];
        }

        // ", em [Cidade]" — última ocorrência
        if (preg_match_all('/,\s*em\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]{2,40}?)(?=[,\.\;]|$)/iu', $text, $m) && ! empty($m[1])) {
            return [null, trim(end($m[1]))];
        }

        // "em [Cidade]" — última ocorrência no texto
        if (preg_match_all('/\bem\s+([A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s]{2,40}?)(?=[,\.\;]|$)/iu', $text, $m) && ! empty($m[1])) {
            return [null, trim(end($m[1]))];
        }

        return [null, null];
    }

    private function cleanLocationName(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = preg_replace('/\b(VÍDEOS|VIDEOS|Imagens cedidas|Veja mais|G1|SP1|SP2|Reprodução|Assista)\b.*$/iu', '', $value);
        $value = trim($value, " \t\n\r\0\x0B.,;-");

        if ($value === '' || mb_strlen($value) < 2) {
            return null;
        }

        $invalidTerms = [
            'reprodução', 'assista', 'vídeo', 'videos', 'vídeos',
            'moradores protestam', 'infraestrutura', 'diversos bairros',
            'pontilhões', 'pontes submersas', 'mananciais', 'mercado',
        ];

        foreach ($invalidTerms as $term) {
            if (str_contains(mb_strtolower($value), mb_strtolower($term))) {
                return null;
            }
        }

        return $value;
    }

    private function isGenericLocation(string $value): bool
    {
        $value = mb_strtolower($value);

        $genericTerms = [
            'áreas mais vulneráveis', 'região', 'vale e região', 'cidade', 'cidades',
            'estado', 'capital', 'interior', 'litoral', 'chuva', 'frente fria',
            'tempo', 'vento', 'bairros', 'ruas', 'avenidas', 'não identificada',
        ];

        foreach ($genericTerms as $term) {
            if (str_contains($value, $term)) {
                return true;
            }
        }

        return false;
    }
}
