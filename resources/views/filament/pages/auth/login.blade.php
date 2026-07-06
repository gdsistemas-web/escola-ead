<div>
    @push('styles')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endpush

    <div class="login-screen">
        <div class="login-container">
            <div class="login-left-panel">
                <div class="left-panel-top">
                    <img src="/assets/logo_escola.png" alt="Escola do Parlamento de Itapevi" class="login-logo">
                    <svg class="left-panel-wave" viewBox="0 0 100 20" preserveAspectRatio="none">
                        <path d="M0,0 C30,15 70,5 100,15 L100,20 L0,20 Z" fill="#023618"></path>
                    </svg>
                </div>
                <div class="left-panel-bottom">
                    <div>
                        <h2 class="left-panel-heading">Conhecimento que transforma vidas e constrói o futuro.</h2>
                        <p class="left-panel-desc">A Escola do Parlamento de Itapevi oferece cursos e capacitações para fortalecer a cidadania e o poder legislativo.</p>
                    </div>

                    <div class="left-panel-features">
                        <div class="feature-item">
                            <div class="feature-icon-box">
                                <i class="ri-book-open-line"></i>
                            </div>
                            <span class="feature-text">Cursos de<br>Qualidade</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-box">
                                <i class="ri-group-line"></i>
                            </div>
                            <span class="feature-text">Professores<br>Especialistas</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-box">
                                <i class="ri-award-line"></i>
                            </div>
                            <span class="feature-text">Certificação<br>Digital</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-box">
                                <i class="ri-line-chart-line"></i>
                            </div>
                            <span class="feature-text">Aprendizado<br>Contínuo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-right-panel" x-data="{ showPassword: false }">
                <h1 class="login-title">Bem-vindo de volta!</h1>
                <p class="login-subtitle">Acesse sua conta para continuar</p>

                <form wire:submit="authenticate" class="login-form">
                    @if (session()->has('error'))
                        <div class="alert alert-danger mb-4" role="alert" style="color: #b91c1c; background-color: #fef2f2; border-color: #fee2e2; padding: 12px; border-radius: 8px; font-size: 14px;">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label" for="email">E-mail</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <i class="ri-mail-line"></i>
                            </span>
                            <input
                                wire:model="data.email"
                                type="email"
                                id="email"
                                required
                                autofocus
                                class="form-input"
                                placeholder="Digite seu e-mail"
                            >
                        </div>
                        @error('data.email')
                            <span class="text-danger small mt-1" style="color: #ed1c24; font-size: 12px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label class="form-label" for="password">Senha</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <i class="ri-lock-line"></i>
                            </span>
                            <input
                                wire:model="data.password"
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                required
                                class="form-input"
                                placeholder="Digite sua senha"
                            >
                            <button
                                type="button"
                                class="input-icon-right"
                                @click="showPassword = !showPassword"
                                aria-label="Mostrar ou ocultar senha"
                            >
                                <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                        @error('data.password')
                            <span class="text-danger small mt-1" style="color: #ed1c24; font-size: 12px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label" for="remember">
                            <input
                                wire:model="data.remember"
                                type="checkbox"
                                id="remember"
                                class="checkbox-input"
                            >
                            <span>Lembrar de mim</span>
                        </label>

                        @if (filament()->hasPasswordReset())
                            <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="forgot-link">
                                Esqueceu sua senha?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="btn-submit" wire:loading.attr="disabled" style="margin-top: 24px;">
                        <span wire:loading.remove>Entrar</span>
                        <span wire:loading>Aguarde...</span>
                    </button>
                </form>

                @if (filament()->hasRegistration())
                    <div class="login-footer">
                        Ainda não tem uma conta?
                        <a href="{{ filament()->getRegistrationUrl() }}" class="register-link">
                            Cadastre-se
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="app-login-footer">
            <span>&copy; 2024 Escola do Parlamento de Itapevi. Todos os direitos reservados.</span>
            <a href="/">
                <img src="/assets/favicon.png" alt="EAD EPI" class="footer-mini-logo">
            </a>
        </div>
    </div>
</div>
