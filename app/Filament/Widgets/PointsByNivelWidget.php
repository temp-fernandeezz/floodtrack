<?php

namespace App\Filament\Widgets;

use App\Models\FloodPoint;
use Filament\Widgets\ChartWidget;

class PointsByNivelWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pontos por nível de severidade';

    protected int | string | array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = FloodPoint::selectRaw('nivel, count(*) as total')
            ->groupBy('nivel')
            ->pluck('total', 'nivel');

        return [
            'labels'   => ['Baixo', 'Médio', 'Alto'],
            'datasets' => [
                [
                    'data'            => [
                        $counts->get('baixo', 0),
                        $counts->get('medio', 0),
                        $counts->get('alto', 0),
                    ],
                    'backgroundColor' => ['#10b981', '#f59e0b', '#ef4444'],
                    'borderWidth'     => 2,
                    'borderColor'     => '#ffffff',
                ],
            ],
        ];
    }
}
