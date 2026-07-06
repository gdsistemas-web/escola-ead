<?php

namespace App\Filament\Resources\Courses\Widgets;

use App\Models\Course;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CourseStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total visível', Course::count())
                ->color('primary'),
            Stat::make('Publicados', Course::where('status', 'published')->count())
                ->color('success'),
            Stat::make('Rascunhos', Course::where('status', 'draft')->count())
                ->color('warning'),
            Stat::make('Em revisão', Course::where('status', 'pending_review')->count())
                ->color('info'),
            Stat::make('Encerrados', Course::where('status', 'closed')->count())
                ->color('gray'),
        ];
    }
}
