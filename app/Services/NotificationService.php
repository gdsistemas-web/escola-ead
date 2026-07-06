<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        bool $mail = false,
        array $mailData = [],
    ): Notification {
        $notification = Notification::firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => $type,
                'url' => $url,
            ],
            [
                'title' => $title,
                'body' => $body,
            ],
        );

        if ($mail && $notification->wasRecentlyCreated && $this->mailEnabled()) {
            $this->sendMail($user, $title, $body, $url, $mailData);
        }

        return $notification;
    }

    public function sendMail(User $user, string $subject, ?string $body = null, ?string $url = null, array $data = []): void
    {
        $this->applyMailSettings();

        $template = $this->templateSettings();
        $absoluteUrl = $url ? url($url) : null;

        try {
            $sentMessage = Mail::send('mail.notification', [
                'user' => $user,
                'subject' => $subject,
                'body' => $body,
                'url' => $absoluteUrl,
                'template' => $template,
                'data' => $data,
            ], function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            });

            Log::info('Notification email sent', [
                'to' => $user->email,
                'subject' => $subject,
                'message_id' => $sentMessage?->getMessageId(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Notification email failed', [
                'to' => $user->email,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function mailEnabled(): bool
    {
        return (bool) ($this->smtpSettings()['enabled'] ?? true);
    }

    private function applyMailSettings(): void
    {
        $settings = $this->smtpSettings();

        if (! $settings) {
            return;
        }

        $mailer = $settings['mailer'] ?? config('mail.default', 'log');
        Config::set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $settings['host'] ?? config('mail.mailers.smtp.host'));
            Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? config('mail.mailers.smtp.port')));
            Config::set('mail.mailers.smtp.username', $settings['username'] ?? null);
            Config::set('mail.mailers.smtp.password', $settings['password'] ?? null);
            Config::set('mail.mailers.smtp.scheme', $this->smtpScheme($settings['scheme'] ?? null, (int) ($settings['port'] ?? 587)));
            Config::set('mail.mailers.smtp.local_domain', $settings['local_domain'] ?? parse_url((string) config('app.url'), PHP_URL_HOST) ?: null);
        }

        Config::set('mail.from.address', $settings['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $settings['from_name'] ?? config('mail.from.name'));
    }

    private function smtpSettings(): array
    {
        return Setting::where('key', 'mail.smtp')->value('value') ?? [];
    }

    private function smtpScheme(?string $scheme, int $port): string
    {
        $scheme = strtolower((string) $scheme);

        return match ($scheme) {
            'ssl', 'smtps' => 'smtps',
            'smtp', 'tls', 'starttls', '' => $port === 465 ? 'smtps' : 'smtp',
            default => 'smtp',
        };
    }

    private function templateSettings(): array
    {
        return array_replace([
            'brand_name' => 'EAD EPI',
            'subtitle' => 'Escola do Parlamento de Itapevi',
            'primary_color' => '#008f43',
            'accent_color' => '#ed1c24',
            'footer_text' => 'Escola do Parlamento de Itapevi - Aprender, participar e transformar.',
            'logo_url' => url('/assets/logo_escola.png'),
        ], Setting::where('key', 'mail.template')->value('value') ?? []);
    }
}
