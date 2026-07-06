<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useEadStore } from '../stores/ead';
import BaseModal from '../components/BaseModal.vue';
import DashboardShell from '../components/DashboardShell.vue';
import EmptyState from '../components/EmptyState.vue';

const router = useRouter();
const store = useEadStore();
const { enrollments } = storeToRefs(store);

const selectedEnrollment = ref(null);
const selectedLesson = ref(null);
const selectedExam = ref(null);
const examDetail = ref(null);
const certificate = ref(null);
const certificateModal = ref(false);
const examModal = ref(false);
const examResultModal = ref(false);
const loadingRoom = ref(false);
const savingProgress = ref(false);
const examSubmitting = ref(false);
const scormRuntimeReady = ref(false);
const scormFrameKey = ref(0);
const progressDraft = ref(95);
const examAttempt = ref(null);
const answers = reactive({});

const sidebarItems = [
    { group: 'Acadêmico', label: 'Meus cursos', icon: 'bi bi-play-circle', active: true },
    { group: 'Acadêmico', label: 'Catálogo', icon: 'bi bi-mortarboard', to: '/cursos' },
    { group: 'Acadêmico', label: 'Certificados', icon: 'bi bi-patch-check' },
    { group: 'Comunicação', label: 'Chat com professor', icon: 'bi bi-chat-dots', to: '/aluno/comunicacao' },
    { group: 'Sistema', label: 'Notificações', icon: 'bi bi-bell' },
    { group: 'Sistema', label: 'Meu perfil', icon: 'bi bi-person' },
];

function statusText(status) {
    return {
        active: 'Ativo',
        completed: 'Concluído',
        cancelled: 'Cancelado',
        waiting: 'Lista de espera',
    }[status] || status || '-';
}

function academicProgress(enrollment) {
    return enrollment?.academic_status?.progress_percent ?? enrollment?.progress_percent ?? 0;
}

function certificateBlockReason(enrollment) {
    const missing = enrollment?.academic_status?.missing_requirements || [];

    if (missing.includes('nota_minima')) {
        return 'Avaliação pendente ou nota mínima não atingida.';
    }

    if (missing.includes('progresso_minimo')) {
        return `Progresso mínimo exigido: ${enrollment?.academic_status?.required_progress_percent || 0}%.`;
    }

    if (missing.includes('matricula_ativa')) {
        return 'Matrícula precisa estar ativa.';
    }

    if (missing.includes('matricula')) {
        return 'Matrícula não localizada.';
    }

    return '';
}

const flatLessons = computed(() => {
    const modules = selectedEnrollment.value?.course?.modules || [];
    return modules.flatMap((module) => (module.lessons || []).map((lesson) => ({ ...lesson, module_title: module.title })));
});

const activeLessonProgress = computed(() => selectedLesson.value?.progress?.[0]?.progress_percent || 0);
const activeLessonCompleted = computed(() => Boolean(selectedLesson.value?.progress?.[0]?.is_completed));
const certificates = computed(() => selectedEnrollment.value?.course?.certificates || []);
const exams = computed(() => selectedEnrollment.value?.course?.exams || []);

function firstPendingLesson(enrollment) {
    const modules = enrollment.course?.modules || [];
    const lessons = modules.flatMap((module) => module.lessons || []);
    return lessons.find((lesson) => !lesson.progress?.[0]?.is_completed) || lessons[0] || null;
}

async function openRoom(enrollment, lesson = null) {
    loadingRoom.value = true;
    selectedEnrollment.value = await store.loadEnrollment(enrollment);
    selectedLesson.value = lesson
        ? flatLessons.value.find((item) => item.id === lesson.id) || firstPendingLesson(selectedEnrollment.value)
        : firstPendingLesson(selectedEnrollment.value);
    progressDraft.value = Math.max(activeLessonProgress.value, 95);
    loadingRoom.value = false;
}

async function refreshRoom() {
    if (!selectedEnrollment.value) {
        return;
    }

    const lessonId = selectedLesson.value?.id;
    selectedEnrollment.value = await store.loadEnrollment(selectedEnrollment.value);
    selectedLesson.value = flatLessons.value.find((lesson) => lesson.id === lessonId) || firstPendingLesson(selectedEnrollment.value);
}

function selectLesson(lesson) {
    selectedLesson.value = lesson;
    progressDraft.value = Math.max(lesson.progress?.[0]?.progress_percent || 0, 95);
}

async function installScormRuntime(lesson) {
    clearScormRuntime();

    if (!lesson || lesson.content_type !== 'scorm') {
        scormRuntimeReady.value = false;
        return;
    }

    scormRuntimeReady.value = false;
    const launch = await store.loadScormLaunch(lesson);
    const values = reactive({ ...(launch.data || {}) });
    let initialized = false;
    let lastError = '0';

    const setError = (code) => {
        lastError = code;
        return code === '0' ? 'true' : 'false';
    };

    const commit = (finished = false) => {
        store.commitScorm(lesson, { ...values }, finished)
            .then(() => Promise.all([refreshRoom(), store.loadEnrollments()]))
            .catch(() => {
                lastError = '101';
            });

        return setError('0');
    };

    window.API = {
        LMSInitialize() {
            initialized = true;
            return setError('0');
        },
        LMSFinish() {
            initialized = false;
            return commit(true);
        },
        LMSGetValue(element) {
            if (!initialized) {
                setError('301');
                return '';
            }

            setError('0');
            return values[element] ?? '';
        },
        LMSSetValue(element, value) {
            if (!initialized) {
                return setError('301');
            }

            values[element] = String(value ?? '');
            return setError('0');
        },
        LMSCommit() {
            return commit(false);
        },
        LMSGetLastError() {
            return lastError;
        },
        LMSGetErrorString(code) {
            return {
                0: 'No error',
                101: 'General exception',
                301: 'Not initialized',
            }[code] || 'Unknown error';
        },
        LMSGetDiagnostic(code) {
            return this.LMSGetErrorString(code || lastError);
        },
    };

    window.API_1484_11 = {
        Initialize: window.API.LMSInitialize,
        Terminate: window.API.LMSFinish,
        GetValue: window.API.LMSGetValue,
        SetValue: window.API.LMSSetValue,
        Commit: window.API.LMSCommit,
        GetLastError: window.API.LMSGetLastError,
        GetErrorString: window.API.LMSGetErrorString,
        GetDiagnostic: window.API.LMSGetDiagnostic,
    };

    scormRuntimeReady.value = true;
    scormFrameKey.value += 1;
}

function clearScormRuntime() {
    delete window.API;
    delete window.API_1484_11;
}

async function markLessonProgress() {
    if (!selectedLesson.value) {
        return;
    }

    savingProgress.value = true;
    const watchedSeconds = Math.round((selectedLesson.value.duration_minutes || 0) * 60 * (progressDraft.value / 100));
    await store.saveLessonProgress(selectedLesson.value, {
        watched_seconds: watchedSeconds,
        progress_percent: progressDraft.value,
    });
    await Promise.all([refreshRoom(), store.loadEnrollments()]);
    savingProgress.value = false;
}

async function issue(enrollment = selectedEnrollment.value) {
    certificate.value = await store.issueCertificate(enrollment.course);
    certificateModal.value = true;
    await Promise.all([refreshRoom(), store.loadEnrollments()]);
}

async function openExam(exam) {
    selectedExam.value = exam;
    examDetail.value = await store.loadExam(exam);
    Object.keys(answers).forEach((key) => delete answers[key]);
    examDetail.value.questions.forEach((question) => {
        answers[question.id] = question.type === 'essay' ? '' : null;
    });
    examModal.value = true;
}

async function submitCurrentExam() {
    examSubmitting.value = true;
    const payload = examDetail.value.questions.map((question) => ({
        question_id: question.id,
        question_option_id: question.type === 'essay' ? null : answers[question.id],
        answer_text: question.type === 'essay' ? answers[question.id] : null,
    }));
    examAttempt.value = await store.submitExam(examDetail.value, payload);
    examModal.value = false;
    examResultModal.value = true;
    await Promise.all([refreshRoom(), store.loadEnrollments()]);
    examSubmitting.value = false;
}

function lessonIcon(lesson) {
    if (lesson.content_type === 'scorm') return 'bi bi-file-earmark-zip';
    if (lesson.content_type === 'pdf') return 'bi bi-file-earmark-pdf';
    if (lesson.content_type === 'external_link') return 'bi bi-box-arrow-up-right';
    return 'bi bi-play-circle';
}

function contentUrl(lesson) {
    return lesson?.content_url || (lesson?.file_path ? `/storage/${lesson.file_path}` : '');
}

onMounted(async () => {
    if (!store.isAuthenticated) {
        router.push('/login');
        return;
    }

    await store.loadEnrollments();
});

watch(() => selectedLesson.value?.id, async () => {
    await installScormRuntime(selectedLesson.value);
});

onUnmounted(() => {
    clearScormRuntime();
});
</script>

<template>
    <DashboardShell
        area="Aluno"
        title="Meus cursos"
        subtitle="Estude aulas, baixe materiais, realize avaliações e acompanhe seus certificados."
        icon="bi bi-play-circle"
        :items="sidebarItems"
        :user="store.user"
    >
        <template #actions>
            <button class="btn btn-outline-primary" @click="store.loadEnrollments()">
                <i class="bi bi-arrow-clockwise me-2"></i>Atualizar
            </button>
        </template>

        <div v-if="!selectedEnrollment" class="learning-overview">
            <div class="learning-summary">
                <div>
                    <span class="section-kicker">Área do aluno</span>
                    <h2>Escolha um curso para continuar</h2>
                    <p>O ambiente de estudo reúne aulas, materiais, provas e certificados em uma trilha única.</p>
                </div>
                <RouterLink class="btn btn-primary" to="/cursos"><i class="bi bi-mortarboard me-2"></i>Ver catálogo</RouterLink>
            </div>

            <div class="row g-3">
                <div v-for="enrollment in enrollments" :key="enrollment.id" class="col-xl-6">
                    <article class="learning-course-card">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <span class="section-kicker">{{ enrollment.course?.category?.name || 'Curso' }}</span>
                                <h3>{{ enrollment.course?.name }}</h3>
                            </div>
                            <span class="badge text-bg-primary align-self-start">{{ statusText(enrollment.status) }}</span>
                        </div>
                        <p>{{ enrollment.course?.short_description }}</p>
                        <div class="d-flex justify-content-between small">
                            <span>Progresso geral</span>
                            <strong>{{ academicProgress(enrollment) }}%</strong>
                        </div>
                        <div class="progress mt-2">
                            <div class="progress-bar" :style="{ width: academicProgress(enrollment) + '%' }"></div>
                        </div>
                        <div class="learning-card-grid">
                            <div><span>Aulas</span><strong>{{ enrollment.academic_status?.completed_required_lessons }}/{{ enrollment.academic_status?.required_lessons }}</strong></div>
                            <div><span>Nota</span><strong>{{ enrollment.academic_status?.final_grade ?? 'Pendente' }}</strong></div>
                            <div><span>Certificado</span><strong>{{ enrollment.academic_status?.certificate_available ? 'Disponível' : 'Em andamento' }}</strong></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button class="btn btn-primary btn-sm" @click="openRoom(enrollment)">
                                <i class="bi bi-play-circle me-1"></i>Continuar
                            </button>
                            <button class="btn btn-outline-success btn-sm" :disabled="!enrollment.academic_status?.certificate_available" @click="issue(enrollment)">
                                <i class="bi bi-patch-check me-1"></i>Emitir certificado
                            </button>
                        </div>
                        <small v-if="!enrollment.academic_status?.certificate_available && certificateBlockReason(enrollment)" class="text-secondary d-block mt-2">{{ certificateBlockReason(enrollment) }}</small>
                    </article>
                </div>
                <div v-if="!enrollments.length" class="col-12">
                    <EmptyState title="Você ainda não possui matrículas" text="Escolha um curso publicado e confirme sua matrícula para começar.">
                        <RouterLink to="/cursos" class="btn btn-primary mt-3">Ver catálogo</RouterLink>
                    </EmptyState>
                </div>
            </div>
        </div>

        <div v-else class="learning-room">
            <div class="learning-room-head">
                <button class="btn btn-outline-secondary btn-sm" @click="selectedEnrollment = null; selectedLesson = null">
                    <i class="bi bi-arrow-left me-1"></i>Meus cursos
                </button>
                <div>
                    <span class="section-kicker">{{ selectedEnrollment.course?.category?.name || 'Curso' }}</span>
                    <h2>{{ selectedEnrollment.course?.name }}</h2>
                    <p>{{ selectedEnrollment.course?.short_description }}</p>
                </div>
                <button class="btn btn-outline-primary btn-sm" :disabled="loadingRoom" @click="refreshRoom">
                    <i class="bi bi-arrow-clockwise me-1"></i>Sincronizar
                </button>
            </div>

            <div class="learning-room-grid">
                <aside class="lesson-playlist">
                    <div class="playlist-block" v-for="module in selectedEnrollment.course?.modules" :key="module.id">
                        <strong>{{ module.title }}</strong>
                        <button
                            v-for="lesson in module.lessons"
                            :key="lesson.id"
                            class="lesson-row"
                            :class="{ active: selectedLesson?.id === lesson.id, done: lesson.progress?.[0]?.is_completed }"
                            @click="selectLesson(lesson)"
                        >
                            <i :class="lesson.progress?.[0]?.is_completed ? 'bi bi-check-circle-fill' : lessonIcon(lesson)"></i>
                            <span>{{ lesson.title }}</span>
                            <small>{{ lesson.progress?.[0]?.progress_percent || 0 }}%</small>
                        </button>
                    </div>
                </aside>

                <main class="lesson-stage">
                    <div v-if="selectedLesson" class="lesson-player">
                        <div class="lesson-player-visual">
                            <template v-if="['youtube', 'vimeo', 'mp4', 'scorm'].includes(selectedLesson.content_type) && contentUrl(selectedLesson)">
                                <video v-if="selectedLesson.content_type === 'mp4'" controls :src="contentUrl(selectedLesson)"></video>
                                <iframe v-else-if="selectedLesson.content_type === 'scorm' && scormRuntimeReady" :key="scormFrameKey" :src="contentUrl(selectedLesson)" title="Aula SCORM" allowfullscreen></iframe>
                                <div v-else-if="selectedLesson.content_type === 'scorm'" class="text-light">Preparando aula SCORM...</div>
                                <iframe v-else :src="contentUrl(selectedLesson)" title="Player da aula" allowfullscreen></iframe>
                            </template>
                            <template v-else>
                                <i :class="lessonIcon(selectedLesson)"></i>
                                <strong>{{ selectedLesson.content_type === 'pdf' ? 'Material em PDF' : 'Conteúdo externo' }}</strong>
                                <a v-if="contentUrl(selectedLesson)" class="btn btn-light btn-sm" :href="contentUrl(selectedLesson)" target="_blank" rel="noopener">Abrir conteúdo</a>
                            </template>
                        </div>

                        <div class="lesson-body">
                            <div>
                                <span class="section-kicker">{{ selectedLesson.module_title }}</span>
                                <h3>{{ selectedLesson.title }}</h3>
                                <p>{{ selectedLesson.description || 'Acompanhe o conteúdo e registre seu progresso quando concluir a etapa.' }}</p>
                            </div>
                            <div class="lesson-progress-box">
                                <div class="d-flex justify-content-between small">
                                    <span>Progresso da aula</span>
                                    <strong>{{ activeLessonProgress }}%</strong>
                                </div>
                                <div class="progress my-2">
                                    <div class="progress-bar" :style="{ width: activeLessonProgress + '%' }"></div>
                                </div>
                                <label class="form-label" for="lesson-progress">Registrar avanço</label>
                                <input id="lesson-progress" v-model.number="progressDraft" class="form-range" type="range" min="0" max="100" step="5">
                                <button class="btn btn-primary w-100" :disabled="savingProgress || activeLessonCompleted" @click="markLessonProgress">
                                    <span v-if="savingProgress" class="spinner-border spinner-border-sm me-2"></span>
                                    {{ activeLessonCompleted ? 'Aula concluída' : 'Salvar progresso' }}
                                </button>
                            </div>
                        </div>

                        <section class="materials-panel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h4>Materiais da aula</h4>
                                <span class="badge text-bg-light">{{ selectedLesson.materials?.length || 0 }}</span>
                            </div>
                            <div v-if="selectedLesson.materials?.length" class="material-list">
                                <a v-for="material in selectedLesson.materials" :key="material.id" :href="`/storage/${material.file_path}`" target="_blank" rel="noopener">
                                    <i class="bi bi-paperclip"></i>
                                    <span>{{ material.title }}</span>
                                    <small>{{ material.mime_type || 'arquivo' }}</small>
                                </a>
                            </div>
                            <p v-else class="text-secondary mb-0">Nenhum material complementar cadastrado para esta aula.</p>
                        </section>
                    </div>
                </main>

                <aside class="learning-side-panel">
                    <section>
                        <h3>Avaliações</h3>
                        <div v-if="exams.length" class="exam-list">
                            <article v-for="exam in exams" :key="exam.id">
                                <div>
                                    <strong>{{ exam.title }}</strong>
                                    <span>{{ exam.attempts?.[0]?.grade ?? 'Sem nota' }} / 10</span>
                                </div>
                                <button class="btn btn-outline-primary btn-sm" @click="openExam(exam)">
                                    {{ exam.attempts?.length >= exam.max_attempts ? 'Revisar' : 'Responder' }}
                                </button>
                            </article>
                        </div>
                        <p v-else class="text-secondary mb-0">Nenhuma avaliação ativa para este curso.</p>
                    </section>

                    <section>
                        <h3>Certificados</h3>
                        <div v-if="certificates.length" class="certificate-mini-list">
                            <a v-for="item in certificates" :key="item.id" :href="`/certificado/${item.code}`" target="_blank" rel="noopener">
                                <i class="bi bi-patch-check"></i>
                                <span>{{ item.code }}</span>
                            </a>
                        </div>
                        <button class="btn btn-success w-100" :disabled="!selectedEnrollment.academic_status?.certificate_available" @click="issue()">
                            Emitir certificado
                        </button>
                        <p v-if="!selectedEnrollment.academic_status?.certificate_available && certificateBlockReason(selectedEnrollment)" class="text-secondary small mt-2 mb-0">{{ certificateBlockReason(selectedEnrollment) }}</p>
                    </section>
                </aside>
            </div>
        </div>

        <BaseModal :show="examModal" :title="examDetail?.title || 'Avaliação'" size="modal-lg" @close="examModal = false">
            <div v-if="examDetail" class="exam-form">
                <p class="text-secondary">{{ examDetail.description || 'Responda todas as questões antes de enviar.' }}</p>
                <article v-for="question in examDetail.questions" :key="question.id" class="exam-question">
                    <strong>{{ question.statement }}</strong>
                    <div v-if="question.type === 'essay'" class="mt-2">
                        <textarea v-model="answers[question.id]" class="form-control" rows="4" placeholder="Digite sua resposta"></textarea>
                    </div>
                    <div v-else class="exam-options">
                        <label v-for="option in question.options" :key="option.id">
                            <input v-model="answers[question.id]" type="radio" :name="`question-${question.id}`" :value="option.id">
                            <span>{{ option.label ? `${option.label}. ` : '' }}{{ option.text }}</span>
                        </label>
                    </div>
                </article>
            </div>
            <template #footer>
                <button class="btn btn-outline-secondary" @click="examModal = false">Fechar</button>
                <button class="btn btn-primary" :disabled="examSubmitting" @click="submitCurrentExam">
                    <span v-if="examSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    Enviar avaliação
                </button>
            </template>
        </BaseModal>

        <BaseModal :show="examResultModal" title="Avaliação enviada" size="modal-sm" @close="examResultModal = false">
            <div v-if="examAttempt" class="success-pulse"><i class="bi bi-check-lg"></i></div>
            <p class="text-center mb-0">
                {{ examAttempt?.grade === null ? 'Sua avaliação foi enviada para correção.' : `Nota registrada: ${examAttempt?.grade}` }}
            </p>
            <template #footer>
                <button class="btn btn-primary" @click="examResultModal = false">Concluir</button>
            </template>
        </BaseModal>

        <BaseModal :show="certificateModal" title="Certificado digital" size="modal-md" @close="certificateModal = false">
            <div v-if="certificate" class="certificate-preview">
                <i class="bi bi-qr-code"></i>
                <strong>{{ certificate.course_name }}</strong>
                <span>Código: {{ certificate.code }}</span>
            </div>
            <template #footer>
                <a v-if="certificate" class="btn btn-primary" :href="`/certificado/${certificate.code}`" target="_blank">Validar certificado</a>
                <button class="btn btn-outline-secondary" @click="certificateModal = false">Fechar</button>
            </template>
        </BaseModal>
    </DashboardShell>
</template>
