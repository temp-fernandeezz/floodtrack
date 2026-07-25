<?php

namespace Tests\Feature\Http;

use App\Models\FloodPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloodPointPublicControllerApiTest extends TestCase
{
    use RefreshDatabase;

    private function makePoint(array $overrides = []): FloodPoint
    {
        return FloodPoint::create(array_merge([
            'cidade' => 'Santos',
            'uf' => 'SP',
            'bairro' => 'Gonzaga',
            'latitude' => -23.96,
            'longitude' => -46.33,
            'nivel' => 'medio',
            'status' => 'ativo',
            'descricao' => 'Ponto de teste',
            'data_ocorrencia' => now(),
            'source_type' => 'manual',
            'review_status' => 'approved',
            'confidence' => 100,
        ], $overrides));
    }

    public function test_api_only_returns_approved_points_with_coordinates(): void
    {
        $approved = $this->makePoint();
        $this->makePoint(['review_status' => 'pending', 'descricao' => 'Pendente com coords']);
        $this->makePoint(['latitude' => null, 'longitude' => null, 'descricao' => 'Aprovado sem coords']);

        $response = $this->getJson('/api/flood-points');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $approved->id]);
    }

    public function test_api_defaults_to_only_active_status(): void
    {
        $ativo = $this->makePoint();
        $this->makePoint(['status' => 'resolvido', 'descricao' => 'Já resolvido']);

        $response = $this->getJson('/api/flood-points');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $ativo->id]);
    }

    public function test_api_pending_returns_points_without_coordinates_regardless_of_review_status(): void
    {
        // Bug histórico: apiPending chegou a exigir review_status=approved e ficava sempre vazio.
        $pendingWithoutCoords = $this->makePoint([
            'review_status' => 'pending', 'latitude' => null, 'longitude' => null,
        ]);
        $approvedWithoutCoords = $this->makePoint([
            'review_status' => 'approved', 'latitude' => null, 'longitude' => null,
        ]);
        $this->makePoint(['descricao' => 'Tem coordenadas, não deve aparecer']);

        $response = $this->getJson('/api/flood-points/pending');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['id' => $pendingWithoutCoords->id]);
        $response->assertJsonFragment(['id' => $approvedWithoutCoords->id]);
    }
}
