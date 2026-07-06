<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validação de certificado - EAD EPI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="certificate-validation-page">
    <header class="site-header" id="acessibilidade">
        <div class="topbar">
            <div class="container">
                <div class="topbar-left">
                    <a href="#conteudo"><strong>1</strong> Ir para o conteúdo</a>
                    <span>|</span>
                    <a href="#menu-principal"><strong>2</strong> Ir para o menu</a>
                    <span>|</span>
                    <a href="#acessibilidade"><strong>3</strong> Acessibilidade do site</a>
                </div>
                <div class="topbar-right">
                    <a href="/validar-certificado"><i class="ri-shield-check-line"></i> Certificados</a>
                    <a href="/gestao"><i class="ri-lock-2-line"></i> Intranet</a>
                </div>
            </div>
        </div>

        <div class="brandbar">
            <div class="container">
                <a class="brand site-brand" href="/" aria-label="Início">
                    <img class="brand-logo brand-logo--school" src="/assets/logo_escola.png" alt="Escola do Parlamento de Itapevi">
                </a>
            </div>
        </div>

        <nav class="mainmenu" id="menu-principal" aria-label="Menu principal">
            <div class="container">
                <ul>
                    <li><a href="/"><i class="ri-home-4-line"></i> Início</a></li>
                    <li><a href="/cursos"><i class="ri-graduation-cap-line"></i> Cursos</a></li>
                    <li><a href="/validar-certificado"><i class="ri-shield-check-line"></i> Certificados</a></li>
                    <li><a href="/contato"><i class="ri-customer-service-2-line"></i> Contato</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="certificate-validation-shell" id="conteudo">
        <section class="certificate-validation-card {{ $certificate['valid'] ? '' : 'is-revoked' }}">
            <div class="certificate-validation-header">
                <div>
                    <span class="section-kicker">EAD EPI</span>
                    <h1>Validação de certificado</h1>
                    <p>Consulta pública de autenticidade emitida pela Escola do Parlamento de Itapevi.</p>
                </div>
                <span class="certificate-status">
                    {{ $certificate['valid'] ? 'Válido' : 'Revogado' }}
                </span>
            </div>

            <div class="certificate-validation-main">
                <div>
                    <span>Aluno(a)</span>
                    <strong>{{ $certificate['student_name'] }}</strong>
                </div>
                <div>
                    <span>Curso</span>
                    <strong>{{ $certificate['course_name'] }}</strong>
                </div>
            </div>

            <div class="certificate-validation-grid">
                <div>
                    <span>Código</span>
                    <strong>{{ $certificate['code'] }}</strong>
                </div>
                <div>
                    <span>Carga horária</span>
                    <strong>{{ $certificate['workload_hours'] }}h</strong>
                </div>
                <div>
                    <span>Conclusão</span>
                    <strong>{{ optional($certificate['completed_at'])->format('d/m/Y') ?? '-' }}</strong>
                </div>
                <div>
                    <span>Emissão</span>
                    <strong>{{ optional($certificate['issued_at'])->format('d/m/Y') ?? '-' }}</strong>
                </div>
            </div>

            <div class="certificate-hash">
                <span>Hash de verificação</span>
                <strong>{{ $certificate['verification_hash'] }}</strong>
            </div>

            @if (! $certificate['valid'])
                <p class="certificate-revoked-reason">
                    Motivo da revogação: {{ $certificate['revoked_reason'] ?? 'não informado' }}
                </p>
            @endif

            <div class="certificate-validation-actions">
                <a href="/validar-certificado" class="btn btn-primary">Consultar outro certificado</a>
                <a href="/" class="btn btn-outline-secondary">Voltar ao portal</a>
            </div>
        </section>
    </main>

    <footer class="site-footer site-footer--itapevi" id="contato">
        <div class="footer-overlay"></div>

        <div class="container footer-inner">
            <div class="row g-4 align-items-start">
                <div class="col-lg-4">
                    <div class="footer-brand">
                        <img class="footer-logo" src="/assets/img/logo_footer.png" alt="Câmara Municipal de Itapevi">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="footer-card">
                        <div class="footer-ribbon">LOCALIZAÇÃO</div>
                        <div class="footer-card-body">
                            <strong>Rua Arnaldo Sérgio Cordeiro das Neves</strong>
                            <span>Itapevi - SP</span>
                            <small>Das 8h às 17h de segunda a sexta-feira</small>
                        </div>
                    </div>

                    <h2 class="footer-subtitle mt-4">Redes Sociais</h2>
                    <div class="footer-social">
                        <a class="footer-social-btn" href="https://www.youtube.com/user/tvcamaraitapevi" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.flickr.com/photos/158510045@N03/albums" target="_blank" rel="noopener" aria-label="Flickr"><i class="ri-flickr-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.facebook.com/camaraitapevi" target="_blank" rel="noopener" aria-label="Facebook"><i class="ri-facebook-fill"></i></a>
                        <a class="footer-social-btn" href="https://br.linkedin.com/company/camaraitapevi" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="ri-linkedin-fill"></i></a>
                        <a class="footer-social-btn" href="https://www.instagram.com/camaraitapevi" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-line"></i></a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="footer-card">
                        <div class="footer-ribbon">FALE CONOSCO</div>
                        <div class="footer-card-body">
                            <strong>(11) 4143-7600</strong>
                            <span>escoladoparlamento@itapevi.sp.leg.br</span>
                        </div>
                    </div>

                    <h2 class="footer-subtitle mt-4">Acesso rápido</h2>
                    <div class="footer-apps">
                        <a class="footer-app-btn" href="/professor">
                            <i class="ri-user-star-line"></i>
                            <span><small>Acessar</small><b>Área do professor</b></span>
                        </a>
                        <a class="footer-app-btn" href="/aluno">
                            <i class="ri-login-circle-line"></i>
                            <span><small>Acessar</small><b>Área do aluno</b></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                <span>&copy; 2026 Escola do Parlamento de Itapevi.</span>
                <span>Plataforma EAD institucional.</span>
            </div>
        </div>
    </footer>
</body>
</html>
