<?php

namespace App\Http\Controllers\Api;

use App\Models\Certificate;
use App\Models\Course;
use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use App\Services\CertificateVerificationService;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index()
    {
        $query = Certificate::with('course', 'user')->latest();

        if (request()->user()->hasRole('aluno')) {
            $query->where('user_id', request()->user()->id);
        } elseif (request()->user()->hasRole('professor')) {
            $query->whereHas('course', fn ($query) => $query->where('teacher_id', request()->user()->id));
        }

        return $query->paginate(30);
    }

    public function store(Request $request, CertificateService $certificates)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        return $certificates->issue(Course::findOrFail($data['course_id']), $request->user());
    }

    public function show(Certificate $certificate)
    {
        abort_unless(
            request()->user()->hasRole('administrador')
                || request()->user()->id === $certificate->user_id
                || $certificate->course?->teacher_id === request()->user()->id,
            403,
        );

        return $certificate->load('course', 'user', 'template');
    }

    public function update(Request $request, Certificate $certificate)
    {
        abort_unless($request->user()->hasRole('administrador'), 403);

        $certificate->update($request->validate(['certificate_template_id' => ['nullable', 'exists:certificate_templates,id']]));

        return $certificate;
    }

    public function destroy(Certificate $certificate)
    {
        abort_unless(request()->user()->hasRole('administrador'), 403);

        $certificate->delete();

        return response()->noContent();
    }

    public function validatePublic(Request $request, string $code, CertificateVerificationService $verification)
    {
        $certificate = Certificate::where('code', $code)
            ->orWhere('verification_hash', $code)
            ->firstOrFail();

        $payload = $verification->publicPayload($certificate);

        if ($request->is('api/*') || $request->expectsJson()) {
            return $payload;
        }

        return view('certificates.validate', ['certificate' => $payload]);
    }

    public function download(Certificate $certificate)
    {
        abort_unless(
            request()->user()->hasRole('administrador') || request()->user()->id === $certificate->user_id,
            403,
        );

        abort_unless($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path), 404);

        return Storage::disk('public')->download($certificate->pdf_path);
    }

    public function revoke(Request $request, Certificate $certificate, ActivityLogger $logger)
    {
        abort_unless($request->user()->hasRole('administrador'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $certificate->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_reason' => $data['reason'],
        ]);
        $logger->log('certificate.revoked', $certificate, ['reason' => $data['reason']], $request);

        return $certificate->refresh();
    }
}
