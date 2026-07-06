<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #123022;
            font-family: DejaVu Sans, sans-serif;
            background: #f4f7f2;
        }

        .page {
            position: relative;
            width: 1123px;
            height: 794px;
            padding: 42px;
            overflow: hidden;
            background: #f6faf7;
        }

        .top-band {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 146px;
            background: #008f43;
        }

        .top-band:after {
            content: "";
            position: absolute;
            right: -80px;
            bottom: -90px;
            width: 420px;
            height: 220px;
            border: 22px solid rgba(255, 255, 255, .22);
            border-radius: 50%;
        }

        .bottom-band {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            height: 112px;
            background: #182536;
        }

        .bottom-band:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 38%;
            height: 100%;
            background: #ed1c24;
            clip-path: polygon(0 0, 100% 0, 82% 100%, 0 100%);
        }

        .certificate {
            position: relative;
            z-index: 2;
            height: 710px;
            padding: 34px 44px 30px;
            border: 2px solid #d8e6dc;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .18);
        }

        .certificate:before {
            content: "";
            position: absolute;
            inset: 14px;
            border: 3px solid #008f43;
        }

        .certificate:after {
            content: "";
            position: absolute;
            inset: 24px;
            border: 1px solid #ed1c24;
        }

        .content {
            position: relative;
            z-index: 2;
            height: 100%;
            text-align: center;
        }

        .logo {
            width: 286px;
            height: auto;
            margin-bottom: 18px;
        }

        .kicker {
            color: #008f43;
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2.8px;
            text-transform: uppercase;
        }

        h1 {
            color: #152033;
            margin: 8px 0 10px;
            font-size: 54px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .intro {
            width: 760px;
            margin: 0 auto;
            color: #4b5b53;
            font-size: 18px;
            line-height: 1.65;
        }

        .student {
            display: inline-block;
            min-width: 690px;
            margin: 25px auto 12px;
            padding: 0 28px 12px;
            color: #0f172a;
            border-bottom: 2px solid #008f43;
            font-size: 36px;
            font-weight: 800;
        }

        .course-label {
            margin: 4px 0 8px;
            color: #5f6f66;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1.6px;
        }

        .course {
            width: 820px;
            margin: 0 auto;
            color: #006d34;
            font-size: 26px;
            font-weight: 800;
            line-height: 1.25;
        }

        .meta-grid {
            width: 760px;
            margin: 26px auto 0;
            border-collapse: collapse;
            font-size: 13px;
        }

        .meta-grid td {
            width: 33.33%;
            padding: 11px 12px;
            border: 1px solid #dce7df;
            background: #f8fbf9;
            text-align: left;
        }

        .meta-grid span {
            display: block;
            color: #617266;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .meta-grid strong {
            display: block;
            margin-top: 4px;
            color: #172033;
            font-size: 13px;
            line-height: 1.25;
        }

        .signatures {
            position: absolute;
            right: 170px;
            bottom: 74px;
            left: 170px;
            display: table;
            width: 610px;
            table-layout: fixed;
        }

        .signature {
            display: table-cell;
            padding: 0 28px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature .line {
            height: 1px;
            margin-bottom: 8px;
            background: #253246;
        }

        .signature strong {
            display: block;
            color: #172033;
            font-size: 12px;
        }

        .signature span {
            display: block;
            color: #617266;
            font-size: 10px;
            margin-top: 2px;
        }

        .qr-box {
            position: absolute;
            right: 42px;
            bottom: 40px;
            width: 118px;
            text-align: center;
        }

        .qr-box img {
            width: 108px;
            height: 108px;
        }

        .qr-box span {
            display: block;
            color: #334155;
            font-size: 8px;
            line-height: 1.2;
        }

        .hash {
            position: absolute;
            right: 42px;
            bottom: 18px;
            left: 42px;
            color: #667085;
            font-size: 8px;
            text-align: left;
        }

        .stamp {
            position: absolute;
            top: 35px;
            right: 44px;
            padding: 8px 12px;
            color: #008f43;
            border: 1px solid #008f43;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('assets/logo_escola.png');
        $logo = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $statusLabel = $certificate->status === 'valid' ? 'Válido' : 'Revogado';
    @endphp

    <div class="page">
        <div class="top-band"></div>
        <div class="bottom-band"></div>

        <main class="certificate">
            <section class="content">
                <div class="stamp">{{ $statusLabel }}</div>

                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="EAD EPI">
                @endif

                <p class="kicker">Escola do Parlamento de Itapevi</p>
                <h1>Certificado</h1>

                <p class="intro">
                    Certificamos que concluiu com aproveitamento a formação oferecida pela
                    plataforma EAD da Escola do Parlamento de Itapevi.
                </p>

                <div class="student">{{ $certificate->student_name }}</div>

                <p class="course-label">Curso</p>
                <div class="course">{{ $certificate->course_name }}</div>

                <table class="meta-grid">
                    <tr>
                        <td>
                            <span>Carga horária</span>
                            <strong>{{ $certificate->workload_hours }} horas</strong>
                        </td>
                        <td>
                            <span>Conclusão</span>
                            <strong>{{ $certificate->completed_at?->format('d/m/Y') }}</strong>
                        </td>
                        <td>
                            <span>Emissão</span>
                            <strong>{{ $certificate->issued_at?->format('d/m/Y H:i') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span>Código de validação</span>
                            <strong>{{ $certificate->code }}</strong>
                        </td>
                        <td>
                            <span>Situação</span>
                            <strong>{{ $statusLabel }}</strong>
                        </td>
                    </tr>
                </table>

                <div class="signatures">
                    <div class="signature">
                        <div class="line"></div>
                        <strong>Coordenação Acadêmica</strong>
                        <span>Escola do Parlamento</span>
                    </div>
                    <div class="signature">
                        <div class="line"></div>
                        <strong>Direção Institucional</strong>
                        <span>Câmara Municipal de Itapevi</span>
                    </div>
                </div>

                <div class="qr-box">
                    <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
                    <span>Valide pelo QR Code</span>
                </div>

                <div class="hash">
                    Hash de verificação: {{ $certificate->verification_hash }} |
                    Consulte: {{ url("/certificado/{$certificate->code}") }}
                </div>
            </section>
        </main>
    </div>
</body>
</html>
