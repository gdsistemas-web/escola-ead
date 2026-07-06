<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Str;

class CertificateVerificationService
{
    public function ensureHash(Certificate $certificate): string
    {
        if ($certificate->verification_hash) {
            return $certificate->verification_hash;
        }

        $certificate->forceFill([
            'verification_hash' => hash('sha256', implode('|', [
                $certificate->code,
                $certificate->course_id,
                $certificate->user_id,
                $certificate->student_name,
                $certificate->course_name,
                $certificate->issued_at?->timestamp ?? now()->timestamp,
                Str::random(32),
            ])),
        ])->save();

        return $certificate->verification_hash;
    }

    public function publicPayload(Certificate $certificate): array
    {
        $this->ensureHash($certificate);

        return [
            'valid' => $certificate->status === 'valid',
            'status' => $certificate->status,
            'code' => $certificate->code,
            'verification_hash' => $certificate->verification_hash,
            'student_name' => $certificate->student_name,
            'course_name' => $certificate->course_name,
            'workload_hours' => $certificate->workload_hours,
            'completed_at' => $certificate->completed_at,
            'issued_at' => $certificate->issued_at,
            'revoked_at' => $certificate->revoked_at,
            'revoked_reason' => $certificate->status === 'revoked' ? $certificate->revoked_reason : null,
        ];
    }
}
