<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\ScormAttempt;
use App\Models\ScormScoData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScormRuntimeService
{
    public function __construct(private readonly CourseCompletionService $completion)
    {
    }

    public function launchData(Lesson $lesson, User $student): array
    {
        $attempt = $this->attempt($lesson, $student);
        $data = $attempt->data()->pluck('value', 'element')->all();

        return [
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'score_raw' => $attempt->score_raw,
            'progress_percent' => $attempt->progress_percent,
            'data' => array_merge($this->defaults($lesson, $student), $data),
        ];
    }

    public function commit(Lesson $lesson, User $student, array $values, bool $finished = false): array
    {
        return DB::transaction(function () use ($lesson, $student, $values, $finished) {
            $attempt = $this->attempt($lesson, $student);

            foreach ($values as $element => $value) {
                ScormScoData::updateOrCreate(
                    ['scorm_attempt_id' => $attempt->id, 'element' => $element],
                    ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]
                );
            }

            $status = $this->normalizeStatus($values['cmi.core.lesson_status'] ?? $values['cmi.completion_status'] ?? $attempt->status);
            $scoreRaw = $this->numeric($values['cmi.core.score.raw'] ?? $values['cmi.score.raw'] ?? $attempt->score_raw);
            $scoreMin = $this->numeric($values['cmi.core.score.min'] ?? $values['cmi.score.min'] ?? $attempt->score_min);
            $scoreMax = $this->numeric($values['cmi.core.score.max'] ?? $values['cmi.score.max'] ?? $attempt->score_max);
            $progress = $this->progressPercent($status, $values, (int) ($attempt->progress_percent ?? 0));
            $sessionSeconds = $this->timeToSeconds($values['cmi.core.session_time'] ?? $values['cmi.session_time'] ?? null);
            $totalSeconds = max((int) $attempt->total_time_seconds, (int) $attempt->total_time_seconds + $sessionSeconds);

            $attempt->update([
                'status' => $status,
                'score_raw' => $scoreRaw,
                'score_min' => $scoreMin,
                'score_max' => $scoreMax,
                'progress_percent' => $progress,
                'session_time_seconds' => $sessionSeconds,
                'total_time_seconds' => $totalSeconds,
                'last_accessed_at' => now(),
            ]);

            $lesson->loadMissing('module.course');

            LessonProgress::updateOrCreate(
                ['lesson_id' => $lesson->id, 'user_id' => $student->id],
                [
                    'watched_seconds' => $totalSeconds,
                    'progress_percent' => $progress,
                    'is_completed' => in_array($status, ['completed', 'passed'], true) || $progress >= 95,
                    'last_accessed_at' => now(),
                ]
            );

            $this->completion->refreshEnrollment($lesson->module->course, $student);

            return $this->launchData($lesson, $student);
        });
    }

    private function attempt(Lesson $lesson, User $student): ScormAttempt
    {
        $lesson->loadMissing('module.course', 'scormPackage');

        abort_unless($lesson->content_type === 'scorm' && $lesson->scormPackage, 404, 'Aula SCORM nao encontrada.');
        abort_unless(
            $student->enrollments()
                ->where('course_id', $lesson->module->course_id)
                ->whereIn('status', ['active', 'completed'])
                ->exists(),
            403,
            'Matricula ativa obrigatoria para acessar SCORM.'
        );

        return ScormAttempt::firstOrCreate(
            ['scorm_package_id' => $lesson->scormPackage->id, 'user_id' => $student->id],
            [
                'lesson_id' => $lesson->id,
                'status' => 'incomplete',
                'last_accessed_at' => now(),
            ]
        );
    }

    private function defaults(Lesson $lesson, User $student): array
    {
        return [
            'cmi.core.student_id' => (string) $student->id,
            'cmi.core.student_name' => $student->name,
            'cmi.core.lesson_location' => '',
            'cmi.core.lesson_status' => 'incomplete',
            'cmi.core.score.raw' => '',
            'cmi.core.score.min' => '',
            'cmi.core.score.max' => '',
            'cmi.core.total_time' => '0000:00:00.00',
            'cmi.core.exit' => '',
            'cmi.suspend_data' => '',
            'cmi.launch_data' => '',
            'cmi.comments' => '',
            'cmi.comments_from_lms' => '',
        ];
    }

    private function normalizeStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'completed', 'complete' => 'completed',
            'passed', 'pass' => 'passed',
            'failed', 'fail' => 'failed',
            default => 'incomplete',
        };
    }

    private function progressPercent(string $status, array $values, int $current): int
    {
        if (in_array($status, ['completed', 'passed'], true)) {
            return 100;
        }

        $measure = $this->numeric($values['cmi.progress_measure'] ?? null);

        if ($measure !== null) {
            return min(100, max($current, (int) round($measure * 100)));
        }

        return max($current, $status === 'failed' ? 100 : 0);
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function timeToSeconds(mixed $value): int
    {
        if (! is_string($value) || $value === '') {
            return 0;
        }

        if (preg_match('/^(\d+):([0-5]?\d):([0-5]?\d)(?:\.\d+)?$/', $value, $matches)) {
            return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }

        return 0;
    }
}
