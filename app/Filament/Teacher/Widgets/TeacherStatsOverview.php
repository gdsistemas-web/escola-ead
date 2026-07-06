<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\Lesson;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumo do professor';

    protected ?string $description = 'Cursos, alunos e participação acadêmica vinculados ao seu perfil.';

    protected function getStats(): array
    {
        $user = auth()->user();
        $courseIds = Course::where('teacher_id', $user->id)->pluck('id');

        return [
            Stat::make('Meus cursos', $courseIds->count())
                ->description('Cursos sob sua responsabilidade')
                ->color('primary'),
            Stat::make('Alunos ativos', Enrollment::whereIn('course_id', $courseIds)->where('status', 'active')->count())
                ->description('Matrículas ativas nos seus cursos')
                ->color('success'),
            Stat::make('Alunos em risco', Enrollment::whereIn('course_id', $courseIds)->where('status', 'active')->where('updated_at', '<', now()->subDays(15))->count())
                ->description('Sem movimentação recente')
                ->color('danger'),
            Stat::make('Aulas', Lesson::whereHas('module.course', fn ($query) => $query->where('teacher_id', $user->id))->count())
                ->description('Conteúdos cadastrados')
                ->color('primary'),
            Stat::make('Dúvidas sem resposta', ForumTopic::whereIn('course_id', $courseIds)->whereDoesntHave('replies')->count())
                ->description('Tópicos que pedem intervenção')
                ->color('warning'),
        ];
    }
}
