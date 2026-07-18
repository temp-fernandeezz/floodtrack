<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationExtractorService
{
    protected string $apiUrl = 'https://api.anthropic.com/v1/messages';

    // Haiku: mais rápido e barato para extração simples de texto
    protected string $model = 'claude-haiku-4-5';

    // Prompt fixo — define o comportamento de extração
    protected string $systemPrompt = <<<'PROMPT'
Você extrai localização de notícias de alagamento no Brasil. Responda APENAS com JSON.

Campos:
- ocorrencia_real: true/false — a notícia está relatando um alagamento/enchente/inundação
  ACONTECENDO ou que ACONTECEU num lugar específico? false se a notícia é sobre outro
  assunto (política, esporte, turismo, crime, animais, obras, previsão do tempo genérica
  sem relato de um alagamento de fato) mesmo que mencione a palavra "alagamento" ou
  "enchente" de passagem ou como contexto histórico.
- cidade: nome do município brasileiro. Ex: "Manaus", "São Paulo", "Boa Vista". MÁXIMO 4 palavras.
- bairro: nome do bairro ou rua. Ex: "Centro", "Jardim América", "Av. Brasil". MÁXIMO 4 palavras.
- uf: sigla do estado, 2 letras maiúsculas. Ex: "AM", "SP", "RR".
- nivel: "baixo", "medio" ou "alto".

REGRAS CRÍTICAS para cidade e bairro:
- Devem ser NOMES PRÓPRIOS curtos, nunca frases ou descrições
- ERRADO: "grande volume de chuva em áreas urbanas" → use null
- ERRADO: "alguns casos" → use null
- ERRADO: "comunidades ribeirinhas" → use null
- ERRADO: "não identificada", "protesto", "campo", "caso de emergência" → use null
- CERTO: "Boa Vista", "Centro", "Rio Branco"
- Se não encontrar um nome de município real, use null
- Não copie trechos da notícia como se fossem nomes de lugares

Exemplos de ocorrencia_real=false: notícia sobre um governador discutindo com manifestantes
numa barragem; lei municipal sobre outro assunto; vídeo de um animal numa rodovia; ranking
de qualidade de vida; previsão de tempo sem relato de alagamento já ocorrido; matéria de
turismo ou esporte que só cita uma enchente antiga como contexto biográfico.

Formato obrigatório (sem texto fora do JSON):
{"ocorrencia_real": true, "cidade": "...", "bairro": "...", "uf": "...", "nivel": "..."}
PROMPT;

    public function isConfigured(): bool
    {
        return ! empty(config('services.anthropic.key'));
    }

    /**
     * Extrai cidade, bairro, UF e nível de severidade de um texto de notícia.
     * Retorna null se a API não estiver configurada ou a extração falhar.
     */
    public function extract(string $title, string $summary): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $apiKey = config('services.anthropic.key');

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(20)
            ->post($this->apiUrl, [
                'model'      => $this->model,
                'max_tokens' => 200,
                'system'     => $this->systemPrompt,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => "Título: {$title}\n\nDescrição: {$summary}",
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Anthropic API retornou erro', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $body = $response->json();
            $text = $body['content'][0]['text'] ?? null;

            if (! $text) {
                return null;
            }

            $decoded = json_decode($this->stripCodeFences($text), true);

            if (! is_array($decoded)) {
                Log::warning('Anthropic retornou JSON inválido', ['text' => $text]);

                return null;
            }

            $nivel = $decoded['nivel'] ?? null;

            return [
                'ocorrencia_real' => (bool) ($decoded['ocorrencia_real'] ?? true),
                'cidade' => $this->normalizeString($decoded['cidade'] ?? null),
                'bairro' => $this->normalizeString($decoded['bairro'] ?? null),
                'uf'     => $this->normalizeUf($decoded['uf'] ?? null),
                'nivel'  => in_array($nivel, ['baixo', 'medio', 'alto']) ? $nivel : 'medio',
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao chamar Anthropic para extração de localização: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * O modelo às vezes responde com o JSON envolto em ```json ... ``` (markdown),
     * o que quebra o json_decode silenciosamente. Remove esse invólucro antes de parsear.
     */
    private function stripCodeFences(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        return trim($text);
    }

    private function normalizeString(?string $value): ?string
    {
        if (! $value || strtolower($value) === 'null') {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Nomes de lugares são curtos — frases longas são descrições, não locais
        if (mb_strlen($value) > 60) {
            return null;
        }

        // Nomes próprios não têm vírgulas internas
        if (substr_count($value, ',') > 0) {
            return null;
        }

        // Palavras que indicam descrição de fenômeno, não nome de lugar
        $descriptionWords = [
            'sistemas', 'drenagem', 'obstrução',
            'volume', 'chuva', 'casos',
            'municípios', 'bairros', 'ruas', 'avenidas',
            'alguns', 'várias', 'diversas',
        ];

        $lower = mb_strtolower($value);
        foreach ($descriptionWords as $word) {
            if (str_contains($lower, $word)) {
                return null;
            }
        }

        return $value;
    }

    private function normalizeUf(?string $value): ?string
    {
        if (! $value || strtolower($value) === 'null') {
            return null;
        }

        $uf = strtoupper(trim($value));

        return preg_match('/^[A-Z]{2}$/', $uf) ? $uf : null;
    }
}
