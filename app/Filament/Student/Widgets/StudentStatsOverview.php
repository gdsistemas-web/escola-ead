<?php

namespace App\Filament\Student\Widgets;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\ForumReply;
use App\Models\ForumReputation;
use App\Models\ForumTopic;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumo do aluno';

    protected ?string $description = 'Sua jornada de aprendizagem na Escola do Parlamento.';

    protected function getStats(): array
    {
        $user = auth()->user();

        return [
            Stat::make('Cursos matriculados', Enrollment::where('user_id', $user->id)->count())
                ->description('Matrículas ativas e histórico')
                ->color('primary'),
            Stat::make('Cursos concluídos', Enrollment::where('user_id', $user->id)->where('status', 'completed')->count())
                ->description('Cursos aptos para certificação')
                ->color('success'),
            Stat::make('Certificados', Certificate::where('user_id', $user->id)->count())
                ->description('Certificados emitidos')
                ->color('success'),
            Stat::make('Participações', ForumTopic::where('user_id', $user->id)->count() + ForumReply::where('user_id', $user->id)->count())
                ->description('Tópicos e respostas no fórum')
                ->color('warning'),
            Stat::make('Reputação', (int) ForumReputation::where('user_id', $user->id)->sum('points'))
                ->description('Pontuação acadêmica')
                ->color('success'),
        ];
    }
}
