<?php

namespace App\Console\Commands;

use App\Models\FloodPoint;
use App\Services\ScraperHealthRecorder;
use App\Settings\FloodTrackScraperSettings;
use Illuminate\Console\Command;

class ExpireFloodPoints extends Command
{
    protected $signature = 'flood:expire-points';

    protected $description = 'Marca como resolvido pontos ativos cuja ocorrência já passou do tempo de expiração configurado';

    public function handle(FloodTrackScraperSettings $settings, ScraperHealthRecorder $healthRecorder): int
    {
        $cutoff = now()->subHours($settings->expira_after_hours);

        $count = FloodPoint::query()
            ->where('status', 'ativo')
            ->where('review_status', 'approved')
            ->whereNotNull('data_ocorrencia')
            ->where('data_ocorrencia', '<', $cutoff)
            ->update(['status' => 'resolvido']);

        $healthRecorder->recordExpire($count);

        $this->info("{$count} ponto(s) marcado(s) como resolvido (mais antigos que {$settings->expira_after_hours}h).");

        return self::SUCCESS;
    }
}
