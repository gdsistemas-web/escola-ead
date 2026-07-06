<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total visível', User::count())
                ->color('primary'),
            Stat::make('Administradores', User::role('administrador')->count())
                ->color('info'),
            Stat::make('Professores', User::role('professor')->count())
                ->color('success'),
            Stat::make('Alunos', User::role('aluno')->count())
                ->color('warning'),
        ];
    }
}
