<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;background:#eaf2f8;color:#0f1f35;font-family:Arial,Helvetica,sans-serif;">
    @php
        $mailLogoPath = public_path('assets/mail-logo-ead-epi.png');
        $logoSource = isset($message) && is_file($mailLogoPath)
            ? $message->embed($mailLogoPath)
            : $template['logo_url'];
    @endphp

    <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
        {{ $data['preheader'] ?? $subject }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eaf2f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #d7e3ee;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:{{ $template['primary_color'] }};padding:18px 26px;border-bottom:4px solid {{ $template['accent_color'] }};">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <div style="font-size:25px;font-weight:800;color:#ffffff;line-height:1;">{{ $template['brand_name'] }}</div>
                                        <div style="margin-top:5px;font-size:11px;font-weight:700;color:#ecfff6;text-transform:uppercase;letter-spacing:.7px;">{{ $template['subtitle'] }}</div>
                                    </td>
                                    <td align="right">
                                        <img src="{{ $logoSource }}" alt="{{ $template['brand_name'] }}" width="130" style="display:block;width:130px;max-width:130px;height:auto;background:#ffffff;border-radius:8px;padding:6px;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px 28px 12px;text-align:center;">
                            <div style="width:54px;height:54px;margin:0 auto 16px;border-radius:50%;background:#e8fff0;border:1px solid #b8e7c8;color:{{ $template['primary_color'] }};font-size:34px;line-height:54px;font-weight:800;">✓</div>
                            <div style="color:{{ $template['primary_color'] }};font-size:11px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase;">{{ $data['kicker'] ?? 'Notificação' }}</div>
                            <h1 style="margin:8px 0 7px;color:#0f172a;font-size:25px;line-height:1.25;">{{ $data['headline'] ?? $subject }}</h1>
                            <div style="color:#5b6b82;font-size:13px;">{{ $template['subtitle'] }}</div>
                        </td>
                    </tr>

                    @if (! empty($data['protocol']))
                        <tr>
                            <td style="padding:10px 28px 18px;">
                                <div style="background:#f2f7fb;border:1px solid #cdddea;border-radius:12px;padding:18px;text-align:center;">
                                    <div style="color:{{ $template['primary_color'] }};font-size:10px;font-weight:800;letter-spacing:1.8px;text-transform:uppercase;">Protocolo de acompanhamento</div>
                                    <div style="margin-top:8px;color:#0f172a;font-size:27px;font-weight:800;letter-spacing:.8px;">{{ $data['protocol'] }}</div>
                                </div>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:8px 28px 8px;">
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.75;">Olá, {{ $user->name }}.</p>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.75;">{{ $body }}</p>

                            @if (! empty($data['summary']))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:20px 0;border:1px solid #dbe6ef;border-radius:10px;overflow:hidden;">
                                    @foreach ($data['summary'] as $label => $value)
                                        <tr>
                                            <td style="width:34%;padding:11px 14px;background:#f8fbfd;border-bottom:1px solid #edf2f6;color:#637083;font-size:12px;font-weight:700;">{{ $label }}</td>
                                            <td style="padding:11px 14px;border-bottom:1px solid #edf2f6;color:#0f172a;font-size:13px;font-weight:700;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @if ($url)
                                <div style="text-align:center;margin:24px 0;">
                                    <a href="{{ $url }}" style="display:inline-block;background:{{ $template['primary_color'] }};color:#ffffff;text-decoration:none;border-radius:9px;padding:13px 22px;font-weight:800;font-size:14px;">
                                        {{ $data['action_label'] ?? 'Acessar plataforma' }}
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>

                    @if (! empty($data['cards']))
                        <tr>
                            <td style="padding:8px 24px 28px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        @foreach ($data['cards'] as $card)
                                            <td width="33.33%" valign="top" style="padding:4px;">
                                                <div style="min-height:96px;border:1px solid #dbe6ef;border-radius:10px;padding:14px;text-align:center;">
                                                    <div style="color:{{ $template['primary_color'] }};font-size:20px;">○</div>
                                                    <div style="margin-top:8px;color:#0f172a;font-size:12px;font-weight:800;">{{ $card['title'] }}</div>
                                                    <div style="margin-top:7px;color:#64748b;font-size:11px;line-height:1.45;">{{ $card['body'] }}</div>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="background:#f7fafc;border-top:1px solid #e3edf5;padding:16px 24px;text-align:center;color:#68778c;font-size:11px;line-height:1.55;">
                            {{ $template['footer_text'] }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
