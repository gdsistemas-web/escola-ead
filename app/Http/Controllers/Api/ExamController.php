<?php

namespace App\Http\Controllers\Api;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Http\Controllers\Controller;
use App\Services\CourseCompletionService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    public function index()
    {
        return Exam::with('course', 'module')->paginate(30);
    }

    public function store(Request $request)
    {
        return Exam::create($request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'course_module_id' => ['nullable', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'minimum_grade' => ['nullable', 'numeric'],
            'time_limit_minutes' => ['nullable', 'integer'],
            'max_attempts' => ['nullable', 'integer'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'correction_type' => ['nullable', 'in:automatic,manual'],
            'is_active' => ['nullable', 'boolean'],
        ]));
    }

    public function show(Exam $exam)
    {
        $exam->load('questions.options');

        $exam->questions->each(function (Question $question) {
            $question->options->each->makeHidden(['is_correct']);
            $question->makeHidden(['correct_answer']);
        });

        return $exam;
    }

    public function update(Request $request, Exam $exam)
    {
        $exam->update($request->validate([
            'course_id' => ['sometimes', 'exists:courses,id'],
            'course_module_id' => ['nullable', 'exists:course_modules,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'minimum_grade' => ['nullable', 'numeric', 'between:0,10'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'correction_type' => ['nullable', 'in:automatic,manual'],
            'is_active' => ['nullable', 'boolean'],
        ]));

        return $exam;
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();

        return response()->noContent();
    }

    public function submit(Request $request, Exam $exam, CourseCompletionService $completion, ActivityLogger $logger)
    {
        $exam->loadMissing('course', 'questions.options');
        $user = $request->user();

        abort_unless($exam->is_active, 422, 'Avaliação indisponível.');
        abort_if($exam->opens_at && now()->lt($exam->opens_at), 422, 'Avaliação ainda não foi aberta.');
        abort_if($exam->closes_at && now()->gt($exam->closes_at), 422, 'Prazo da avaliacao encerrado.');
        abort_unless(
            $exam->course->enrollments()->where('user_id', $user->id)->whereIn('status', ['active', 'completed'])->exists(),
            403,
            'Matricula ativa obrigatoria para realizar avaliacao.'
        );
        abort_if(
            $exam->attempts()->where('user_id', $user->id)->count() >= $exam->max_attempts,
            422,
            'Limite de tentativas atingido.'
        );

        $payload = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.question_option_id' => ['nullable', 'integer', 'exists:question_options,id'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        $answers = collect($payload['answers']);
        $questionIds = $answers->pluck('question_id')->unique();
        $questions = Question::with('options')
            ->where('exam_id', $exam->id)
            ->whereIn('id', $questionIds)
            ->get();

        if ($questions->count() !== $questionIds->count()) {
            throw ValidationException::withMessages([
                'answers' => 'Todas as questoes respondidas devem pertencer a avaliacao.',
            ]);
        }

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'attempt_number' => $exam->attempts()->where('user_id', $user->id)->count() + 1,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $score = 0;
        $objectiveWeight = 0;
        $requiresManualGrading = $exam->correction_type === 'manual';

        foreach ($answers as $answer) {
            $question = $questions->firstWhere('id', $answer['question_id']);
            $selected = $question->options->firstWhere('id', $answer['question_option_id'] ?? null);
            $isEssay = $question->type === 'essay';
            $requiresManualGrading = $requiresManualGrading || $isEssay;

            if ($selected && $selected->question_id !== $question->id) {
                throw ValidationException::withMessages([
                    'answers' => 'A alternativa selecionada não pertence à questão informada.',
                ]);
            }

            $isCorrect = $isEssay ? null : (bool) $selected?->is_correct;

            if (! $isEssay) {
                $objectiveWeight += (float) $question->weight;
                $score += $isCorrect ? (float) $question->weight : 0;
            }

            ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id' => $answer['question_id'],
                'question_option_id' => $answer['question_option_id'] ?? null,
                'answer_text' => $answer['answer_text'] ?? null,
                'is_correct' => $isCorrect,
                'score' => $isEssay ? null : ($isCorrect ? $question->weight : 0),
            ]);
        }

        $grade = $objectiveWeight > 0 ? round(min(10, ($score / $objectiveWeight) * 10), 2) : null;
        $attempt->update([
            'grade' => $requiresManualGrading ? null : $grade,
            'status' => $requiresManualGrading ? 'submitted' : 'graded',
        ]);

        if (! $requiresManualGrading) {
            $completion->refreshEnrollment($exam->course, $user);
        }

        $logger->log('exam.submitted', $attempt, ['grade' => $attempt->grade, 'status' => $attempt->status], $request);

        return $attempt->load('answers');
    }

    public function gradeAttempt(Request $request, ExamAttempt $attempt, CourseCompletionService $completion, ActivityLogger $logger)
    {
        abort_unless($request->user()->hasAnyRole(['administrador', 'professor']), 403, 'Permissao insuficiente.');

        $attempt->loadMissing('exam.course', 'answers.question');

        if ($request->user()->hasRole('professor') && $attempt->exam->course->teacher_id !== $request->user()->id) {
            abort(403, 'Apenas o professor responsavel pode corrigir esta avaliacao.');
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.id' => ['required', 'integer', 'exists:exam_answers,id'],
            'answers.*.score' => ['required', 'numeric', 'min:0'],
            'answers.*.feedback' => ['nullable', 'string'],
        ]);

        $totalWeight = 0;
        $totalScore = 0;
        $attemptAnswerIds = $attempt->answers->pluck('id')->all();

        if (collect($data['answers'])->pluck('id')->diff($attemptAnswerIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Todas as respostas corrigidas devem pertencer a tentativa informada.',
            ]);
        }

        foreach ($attempt->answers as $answer) {
            $submitted = collect($data['answers'])->firstWhere('id', $answer->id);
            $score = (float) ($answer->score ?? 0);

            if ($submitted) {
                $maxScore = (float) $answer->question->weight;
                $score = min($maxScore, (float) $submitted['score']);
                $answer->update([
                    'score' => $score,
                    'is_correct' => $score >= $maxScore,
                    'feedback' => $submitted['feedback'] ?? $answer->feedback,
                ]);
            }

            $totalWeight += (float) $answer->question->weight;
            $totalScore += $score;
        }

        $attempt->update([
            'grade' => $totalWeight > 0 ? round(min(10, ($totalScore / $totalWeight) * 10), 2) : null,
            'status' => 'graded',
        ]);

        $completion->refreshEnrollment($attempt->exam->course, $attempt->user);
        $logger->log('exam.graded', $attempt, ['grade' => $attempt->grade], $request);

        return $attempt->fresh()->load('answers.question');
    }
}
