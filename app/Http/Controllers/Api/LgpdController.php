<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\LgpdService;
use Illuminate\Http\Request;

class LgpdController extends Controller
{
    public function terms()
    {
        return [
            'terms_version' => LgpdService::CURRENT_TERMS_VERSION,
            'privacy_policy_version' => LgpdService::CURRENT_PRIVACY_VERSION,
            'summary' => 'Dados usados para autenticação, matrículas, progresso, avaliações, certificados, comunicação acadêmica e auditoria institucional.',
        ];
    }

    public function accept(Request $request, LgpdService $lgpd, ActivityLogger $logger)
    {
        $lgpd->accept($request->user());
        $logger->log('lgpd.accepted', $request->user(), ['version' => LgpdService::CURRENT_TERMS_VERSION], $request);

        return $request->user()->load('profile');
    }

    public function export(Request $request, LgpdService $lgpd, ActivityLogger $logger)
    {
        $logger->log('lgpd.exported', $request->user(), null, $request);

        return $lgpd->export($request->user());
    }

    public function requestAnonymization(Request $request, LgpdService $lgpd, ActivityLogger $logger)
    {
        $lgpd->requestAnonymization($request->user());
        $logger->log('lgpd.anonymization_requested', $request->user(), null, $request);

        return response()->noContent();
    }

    public function anonymizeUser(Request $request, User $user, LgpdService $lgpd, ActivityLogger $logger)
    {
        abort_unless($request->user()->hasRole('administrador'), 403);

        $logger->log('lgpd.user_anonymized', $user, ['admin_id' => $request->user()->id], $request);

        return $lgpd->anonymize($user);
    }
}
