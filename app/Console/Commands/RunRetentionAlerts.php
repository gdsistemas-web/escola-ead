<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class RunRetentionAlerts extends Command
{
    protected $signature = 'lms:retention-alerts {--forum-hours=48} {--mail}';

    protected $description = 'Gera alertas de abandono, pendências e dúvidas sem resposta do LMS.';

    public function handle(RetentionService $retention): int
    {
        $mail = (bool) $this->option('mail');
        $studentAlerts = $retention->runStudentInactivityAlerts($mail);
        $forumAlerts = $retention->runForumSlaAlerts((int) $this->option('forum-hours'), $mail);

        $this->info('Alertas de alunos: '.json_encode($studentAlerts));
        $this->info("Alertas de forum: {$forumAlerts}");

        return self::SUCCESS;
    }
}
