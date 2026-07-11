<?php

namespace App\Filament\Widgets;

use App\Models\FloodPoint;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PointsOverTimeWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Ocorrências nos últimos 30 dias';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $counts = FloodPoint::selectRaw('DATE(data_ocorrencia) as dia, count(*) as total')
            ->where('data_ocorrencia', '>=', $start)
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $day      = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($day)->format('d/m');
            $data[]   = $counts->get($day, 0);
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Ocorrências',
                    'data'            => $data,
                    'borderColor'     => '#7c3aed',
                    'backgroundColor' => 'rgba(124,58,237,0.1)',
                    'fill'            => true,
                    'tension'         => 0.3,
                    'pointRadius'     => 3,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
