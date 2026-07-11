<?php

namespace App\Services\FloodNews;

/**
 * Resultado da extração de local/severidade de uma notícia (via IA e/ou regex).
 */
final class ExtractedLocation
{
    public function __construct(
        public readonly ?string $bairro,
        public readonly ?string $cidade,
        public readonly ?string $uf,
        public readonly string $nivel,
        public readonly int $confidence,
    ) {
    }

    public function hasIdentifiableCity(): bool
    {
        return $this->cidade !== null;
    }
}
