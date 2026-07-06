<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LgpdService
{
    public const CURRENT_TERMS_VERSION = '2026-06-15';

    public const CURRENT_PRIVACY_VERSION = '2026-06-15';

    public function accept(User $user): void
    {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'lgpd_consent_at' => now(),
                'terms_accepted_at' => now(),
                'lgpd_consent_version' => self::CURRENT_TERMS_VERSION,
                'privacy_policy_version' => self::CURRENT_PRIVACY_VERSION,
            ],
        );
    }

    public function export(User $user): array
    {
        $user->load([
            'profile',
            'roles',
            'enrollments.course',
            'certificates.course',
            'forumTopics',
            'forumReplies',
            'forumReputation',
            'notifications',
        ]);

        $user->profile()->updateOrCreate(['user_id' => $user->id], ['data_exported_at' => now()]);

        return [
            'exported_at' => now()->toIso8601String(),
            'user' => $user,
            'privacy' => [
                'terms_version' => $user->profile?->lgpd_consent_version,
                'privacy_policy_version' => $user->profile?->privacy_policy_version,
                'lgpd_consent_at' => $user->profile?->lgpd_consent_at,
                'terms_accepted_at' => $user->profile?->terms_accepted_at,
            ],
        ];
    }

    public function requestAnonymization(User $user): void
    {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['anonymization_requested_at' => now()],
        );
    }

    public function anonymize(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $anonymousEmail = "anon-{$user->id}-".Str::lower(Str::random(10)).'@anon.local';

            $user->update([
                'name' => "Usuario anonimizado {$user->id}",
                'email' => $anonymousEmail,
                'password' => Str::password(32),
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document' => null,
                    'phone' => null,
                    'birthdate' => null,
                    'city' => null,
                    'state' => null,
                    'avatar_path' => null,
                    'anonymized_at' => now(),
                ],
            );

            $user->tokens()->delete();

            return $user->refresh();
        });
    }
}
