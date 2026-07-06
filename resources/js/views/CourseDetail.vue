<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEadStore } from '../stores/ead';
import BaseModal from '../components/BaseModal.vue';

const route = useRoute();
const router = useRouter();
const store = useEadStore();
const course = ref(null);
const loading = ref(true);
const enrollModal = ref(false);
const successModal = ref(false);
const message = ref('');
const enrolling = ref(false);
const enrollmentError = ref('');
const enrollment = ref(null);
const enrollmentForm = ref({
    document: '',
    phone: '',
    birthdate: '',
    city: '',
    state: 'SP',
    education_level: '',
    occupation: '',
    institution: '',
    motivation: '',
    accessibility_needs: '',
    accept_terms: false,
});

const lessonsCount = computed(() => course.value?.modules?.reduce((total, module) => total + (module.lessons?.length || 0), 0) || 0);
const materialsCount = computed(() => course.value?.modules?.reduce((total, module) => (
    total + (module.lessons || []).reduce((lessonTotal, lesson) => lessonTotal + (lesson.materials?.length || 0), 0)
), 0) || 0);

async function enroll() {
    if (!store.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: route.fullPath } });
        return;
    }

    await store.ensureSession();
    enrollmentForm.value.document = store.user?.profile?.document || enrollmentForm.value.document;
    enrollmentForm.value.phone = store.user?.profile?.phone || enrollmentForm.value.phone;
    enrollmentForm.value.birthdate = store.user?.profile?.birthdate || enrollmentForm.value.birthdate;
    enrollmentForm.value.city = store.user?.profile?.city || enrollmentForm.value.city;
    enrollmentForm.value.state = store.user?.profile?.state || enrollmentForm.value.state || 'SP';
    enrollmentError.value = '';
    enrollModal.value = true;
}

async function confirmEnroll() {
    enrollmentError.value = '';
    enrolling.value = true;

    try {
        enrollment.value = await store.enroll(course.value, enrollmentForm.value);
        const protocol = enrollment.value?.application_data?.protocol;
        message.value = `Matrícula registrada em ${course.value.name}.${protocol ? ` Protocolo: ${protocol}.` : ''}`;
        enrollModal.value = false;
        successModal.value = true;
    } catch (error) {
        const errors = error.response?.data?.errors;
        enrollmentError.value = errors ? Object.values(errors).flat().join(' ') : (error.response?.data?.message || 'Não foi possível concluir a matrícula.');
    } finally {
        enrolling.value = false;
    }
}

onMounted(async () => {
    loading.value = true;
    course.value = await store.loadPublicCourse(route.params.slug);
    loading.value = false;
});
</script>

<template>
    <section v-if="loading" class="container py-5">
        <div class="placeholder-glow">
            <span class="placeholder col-6"></span>
            <span class="placeholder col-12 mt-3"></span>
            <span class="placeholder col-10 mt-2"></span>
        </div>
    </section>

    <section v-else class="course-detail-page">
        <div class="course-detail-hero">
            <div class="container">
                <RouterLink class="back-link" to="/cursos"><i class="bi bi-arrow-left"></i>Catálogo</RouterLink>
                <div class="course-detail-grid">
                    <div>
                        <span class="section-kicker">{{ course.category?.name || 'Curso EAD' }}</span>
                        <h1>{{ course.name }}</h1>
                        <p>{{ course.description || course.short_description }}</p>
                        <div class="course-detail-actions">
                            <button class="btn btn-primary btn-lg" @click="enroll">
                                <i class="bi bi-person-plus me-2"></i>Matricular-se
                            </button>
                            <a class="btn btn-outline-light btn-lg" href="#conteudo">
                                <i class="bi bi-list-check me-2"></i>Ver conteúdo
                            </a>
                        </div>
                    </div>
                    <aside class="course-facts" aria-label="Resumo do curso">
                        <div><span>Carga horária</span><strong>{{ course.workload_hours }}h</strong></div>
                        <div><span>Aulas</span><strong>{{ lessonsCount }}</strong></div>
                        <div><span>Avaliações</span><strong>{{ course.exams?.length || 0 }}</strong></div>
                        <div><span>Certificação</span><strong>{{ course.minimum_progress_percent }}%</strong></div>
                    </aside>
                </div>
            </div>
        </div>

        <div class="container py-5">
            <div class="row g-4">
                <div class="col-lg-8">
                    <section id="conteudo" class="content-band">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <span class="section-kicker">Programa</span>
                                <h2 class="h3 mb-1">O que você vai estudar</h2>
                                <p class="text-secondary mb-0">{{ lessonsCount }} aula(s), {{ materialsCount }} material(is) e trilha organizada por módulos.</p>
                            </div>
                        </div>

                        <div class="module-list">
                            <article v-for="module in course.modules" :key="module.id" class="module-row">
                                <div>
                                    <strong>{{ module.title }}</strong>
                                    <span>{{ module.lessons?.length || 0 }} aula(s)</span>
                                </div>
                                <ul>
                                    <li v-for="lesson in module.lessons" :key="lesson.id">
                                        <i class="bi bi-play-circle"></i>
                                        <span>{{ lesson.title }}</span>
                                    </li>
                                </ul>
                            </article>
                        </div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <aside class="enrollment-panel">
                        <span class="section-kicker">Inscrição</span>
                        <h2 class="h4">Pronto para começar?</h2>
                        <p class="text-secondary">A matrícula libera o acompanhamento do progresso, avaliações e emissão de certificado ao concluir os critérios.</p>
                        <div class="teacher-line mb-3">
                            <i class="bi bi-person-workspace"></i>
                            <span>{{ course.teacher?.name || 'Equipe EAD EPI' }}</span>
                        </div>
                        <button class="btn btn-primary w-100" @click="enroll">Matricular-se</button>
                        <a class="btn btn-outline-secondary w-100 mt-2" href="/aluno/comunicacao">Tirar dúvida com professor</a>
                    </aside>
                </div>
            </div>
        </div>

        <BaseModal :show="enrollModal" title="Formulário de matrícula" size="modal-lg" @close="enrollModal = false">
            <p class="mb-2">Você está se matriculando em:</p>
            <div class="selection-card">
                <strong>{{ course?.name }}</strong>
                <span>{{ course?.workload_hours }}h - {{ course?.teacher?.name }}</span>
            </div>

            <div v-if="enrollmentError" class="alert alert-danger mt-3 mb-0">{{ enrollmentError }}</div>

            <form class="enrollment-form mt-3" @submit.prevent="confirmEnroll">
                <div class="form-section-title">Dados pessoais</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="document">CPF/documento</label>
                        <input id="document" v-model="enrollmentForm.document" class="form-control" required maxlength="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="phone">Telefone</label>
                        <input id="phone" v-model="enrollmentForm.phone" class="form-control" required maxlength="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="birthdate">Nascimento</label>
                        <input id="birthdate" v-model="enrollmentForm.birthdate" type="date" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="city">Cidade</label>
                        <input id="city" v-model="enrollmentForm.city" class="form-control" required maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="state">UF</label>
                        <input id="state" v-model="enrollmentForm.state" class="form-control text-uppercase" required maxlength="2">
                    </div>
                </div>

                <div class="form-section-title mt-4">Perfil acadêmico/profissional</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="education">Escolaridade</label>
                        <select id="education" v-model="enrollmentForm.education_level" class="form-select">
                            <option value="">Selecione</option>
                            <option>Ensino médio</option>
                            <option>Ensino técnico</option>
                            <option>Graduação</option>
                            <option>Pós-graduação</option>
                            <option>Servidor público</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="occupation">Ocupação</label>
                        <input id="occupation" v-model="enrollmentForm.occupation" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="institution">Instituição/órgão</label>
                        <input id="institution" v-model="enrollmentForm.institution" class="form-control" maxlength="160">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="motivation">Por que deseja participar?</label>
                        <textarea id="motivation" v-model="enrollmentForm.motivation" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="accessibility">Necessidade de acessibilidade ou observação</label>
                        <textarea id="accessibility" v-model="enrollmentForm.accessibility_needs" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                </div>

                <label class="lgpd-label mt-3">
                    <input v-model="enrollmentForm.accept_terms" type="checkbox" class="lgpd-checkbox" required>
                    <span>Declaro que as informações são verdadeiras e autorizo o uso dos dados para gestão acadêmica, comunicação e emissão de certificado.</span>
                </label>
            </form>
            <template #footer>
                <button class="btn btn-outline-secondary" @click="enrollModal = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="enrolling || !enrollmentForm.accept_terms" @click="confirmEnroll">
                    <i class="bi bi-check2-circle me-2"></i>{{ enrolling ? 'Enviando...' : 'Enviar matrícula' }}
                </button>
            </template>
        </BaseModal>

        <BaseModal :show="successModal" title="Matrícula realizada" size="modal-sm" @close="successModal = false">
            <div class="success-pulse"><i class="bi bi-check-lg"></i></div>
            <p class="text-center mb-0">{{ message }}</p>
            <template #footer>
                <RouterLink class="btn btn-primary" to="/aluno">Ir para área do aluno</RouterLink>
            </template>
        </BaseModal>
    </section>
</template>
