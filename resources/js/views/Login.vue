<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEadStore } from '../stores/ead';

const router = useRouter();
const route = useRoute();
const store = useEadStore();
const mode = ref('login');
const busy = ref(false);
const error = ref('');
const showPassword = ref(false);

const form = reactive({
    name: '',
    email: 'admin@itapevi.sp.leg.br',
    password: 'password',
    lgpd_consent: true,
});

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        if (mode.value === 'login') {
            await store.login({ email: form.email, password: form.password });
        } else {
            await store.register(form);
        }
        const redirect = String(route.query.redirect || '');
        if (redirect && redirect.startsWith('/') && !redirect.startsWith('//')) {
            router.push(redirect);
            return;
        }

        if (store.role === 'aluno') {
            window.location.href = '/aluno';
            return;
        }

        if (store.role === 'professor') {
            window.location.href = '/professor';
            return;
        }

        window.location.href = '/gestao';
    } catch (exception) {
        error.value = exception.response?.data?.message || 'Não foi possível autenticar.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <section class="login-screen">
        <div class="login-container">
            <!-- Left panel -->
            <div class="login-left-panel">
                <div class="left-panel-top">
                    <img :src="'/assets/logo_escola.png'" alt="Escola do Parlamento de Itapevi" class="login-logo">
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

            <!-- Right panel -->
            <div class="login-right-panel">
                <h1 class="login-title">
                    {{ mode === 'login' ? 'Bem-vindo de volta!' : 'Criar cadastro' }}
                </h1>
                <p class="login-subtitle">
                    {{ mode === 'login' ? 'Acesse sua conta para continuar' : 'Preencha os dados abaixo para se cadastrar' }}
                </p>

                <form @submit.prevent="submit" class="login-form">
                    <div v-if="error" class="alert alert-danger mb-4" role="alert" style="color: #b91c1c; background-color: #fef2f2; border-color: #fee2e2; padding: 12px; border-radius: 8px; font-size: 14px;">
                        {{ error }}
                    </div>

                    <!-- Name Field (Register Only) -->
                    <div v-if="mode === 'register'" class="form-group">
                        <label class="form-label">Nome</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <i class="ri-user-line"></i>
                            </span>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                required 
                                class="form-input" 
                                placeholder="Digite seu nome completo"
                            >
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group" :style="mode === 'register' ? 'margin-top: 16px;' : ''">
                        <label class="form-label">E-mail</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <i class="ri-mail-line"></i>
                            </span>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                required 
                                class="form-input" 
                                placeholder="Digite seu e-mail"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group" style="margin-top: 16px;">
                        <label class="form-label">Senha</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <i class="ri-lock-line"></i>
                            </span>
                            <input 
                                v-model="form.password" 
                                :type="showPassword ? 'text' : 'password'" 
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
                    </div>

                    <!-- LGPD Checkbox (Register Only) -->
                    <div v-if="mode === 'register'" style="margin-top: 20px;">
                        <label class="lgpd-label">
                            <input 
                                v-model="form.lgpd_consent" 
                                type="checkbox" 
                                required 
                                class="lgpd-checkbox"
                            >
                            <span>Aceito os termos de uso e a política de privacidade.</span>
                        </label>
                    </div>

                    <!-- Options (Remember me & Forgot password - Login Only) -->
                    <div v-if="mode === 'login'" class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" class="checkbox-input">
                            <span>Lembrar de mim</span>
                        </label>
                        <a href="/recuperar-senha" class="forgot-link" @click.prevent="error = 'Recuperação de senha será liberada pela equipe da Escola do Parlamento.'">
                            Esqueceu sua senha?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-submit" :disabled="busy" style="margin-top: 24px;">
                        <i v-if="busy" class="ri-loader-4-line ri-spin me-2"></i>
                        <span>{{ busy ? 'Aguarde...' : (mode === 'login' ? 'Entrar' : 'Cadastrar') }}</span>
                    </button>
                </form>
                <!-- Footer Links -->
                <div class="login-footer">
                    <span>
                        {{ mode === 'login' ? 'Ainda não tem uma conta? ' : 'Já possui uma conta? ' }}
                    </span>
                    <a href="#" class="register-link" @click.prevent="mode = (mode === 'login' ? 'register' : 'login')">
                        {{ mode === 'login' ? 'Cadastre-se' : 'Entrar' }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Mini Footer -->
        <div class="app-login-footer">
            <span>&copy; 2024 Escola do Parlamento de Itapevi. Todos os direitos reservados.</span>
            <a href="/">
                <img :src="'/assets/favicon.png'" alt="EAD EPI" class="footer-mini-logo">
            </a>
        </div>
    </section>
</template>
