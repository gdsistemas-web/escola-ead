<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    public function __construct(
        private readonly CourseCompletionService $completion,
        private readonly ForumService $forum,
        private readonly NotificationService $notifications,
        private readonly TeacherNotificationService $teacherNotifications,
        private readonly ActivityLogger $logger,
        private readonly CertificateVerificationService $verification,
    )
    {
    }

    public function issue(Course $course, User $student): Certificate
    {
        $status = $this->completion->status($course, $student);

        if (! $status['eligible']) {
            throw ValidationException::withMessages([
                'certificate' => 'O certificado ainda não está disponível para este curso.',
                'requirements' => $status['missing'],
            ]);
        }

        $this->completion->completeIfEligible($course, $student);

        $template = CertificateTemplate::where('is_default', true)->first();

        $certificate = Certificate::firstOrCreate(
            ['course_id' => $course->id, 'user_id' => $student->id],
            [
                'certificate_template_id' => $template?->id,
                'code' => Str::upper(Str::random(12)),
                'student_name' => $student->name,
                'course_name' => $course->name,
                'workload_hours' => $course->workload_hours,
                'completed_at' => now()->toDateString(),
                'issued_at' => now(),
            ]
        );
        $this->verification->ensureHash($certificate);

        if (! $certificate->pdf_path) {
            $certificate->pdf_path = $this->renderPdf($certificate);
            $certificate->save();
        }

        $this->forum->reputation($student, 'certificate_issued', ForumService::POINTS['certificate_issued'], $certificate, courseId: $course->id);
        $this->notifications->notify(
            $student,
            "certificate_issued_{$certificate->id}",
            'Certificado emitido',
            "Seu certificado do curso {$course->name} está disponível. Código: {$certificate->code}.",
            "/certificado/{$certificate->code}",
            true,
        );
        $this->teacherNotifications->certificateIssued($certificate);
        $this->logger->log('certificate.issued', $certificate, ['code' => $certificate->code]);

        return $certificate;
    }

    public function renderPdf(Certificate $certificate): string
    {
        $qr = base64_encode(QrCode::format('svg')->size(140)->generate(url("/certificado/{$certificate->code}")));
        $pdf = Pdf::loadView('pdf.certificate', compact('certificate', 'qr'))->setPaper('a4', 'landscape');
        $path = "certificates/{$certificate->code}.pdf";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
