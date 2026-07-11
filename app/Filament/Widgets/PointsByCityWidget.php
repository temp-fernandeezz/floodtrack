<?php

namespace App\Filament\Widgets;

use App\Models\FloodPoint;
use Filament\Widgets\ChartWidget;

class PointsByCityWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Top 10 cidades com mais ocorrências';

    protected int | string | array $columnSpan = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = FloodPoint::selectRaw('cidade, count(*) as total')
            ->groupBy('cidade')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels'   => $rows->pluck('cidade')->toArray(),
            'datasets' => [
                [
                    'label'           => 'Ocorrências',
                    'data'            => $rows->pluck('total')->toArray(),
                    'backgroundColor' => '#7c3aed',
                    'borderRadius'    => 4,
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
