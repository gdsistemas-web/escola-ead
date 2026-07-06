<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useEadStore } from '../stores/ead';
import BaseModal from '../components/BaseModal.vue';
import DashboardShell from '../components/DashboardShell.vue';
import EmptyState from '../components/EmptyState.vue';

const router = useRouter();
const store = useEadStore();
const { courses } = storeToRefs(store);
const selectedCourse = ref(null);
const courseModal = ref(false);
const lessonModal = ref(false);
const scormModal = ref(false);
const importingScorm = ref(false);
const scormError = ref('');
const lessonForm = ref({ title: '', content_type: 'youtube', content_url: '', duration_minutes: 30 });
const scormForm = ref({ category_id: '', course_name: '', package: null });

const sidebarItems = [
    { group: 'Acadêmico', label: 'Meus cursos', icon: 'bi bi-collection-play', active: true },
    { group: 'Conteúdo', label: 'Aulas e materiais', icon: 'bi bi-file-earmark-play', to: '/gestão/lessons' },
    { group: 'Conteúdo', label: 'Avaliações', icon: 'bi bi-ui-checks-grid', to: '/gestão/exams' },
    { group: 'Conteúdo', label: 'Banco de questões', icon: 'bi bi-question-diamond', to: '/gestão/questions' },
    { group: 'Acadêmico', label: 'Alunos', icon: 'bi bi-people', to: '/gestão/enrollments' },
    { group: 'Comunicação', label: 'Fórum acadêmico', icon: 'bi bi-chat-dots', to: '/comunicacao' },
    { group: 'Sistema', label: 'Gestão completa', icon: 'bi bi-columns-gap', to: '/gestão' },
];

function statusText(status) {
    return {
        draft: 'Rascunho',
        pending_review: 'Em revisão',
        changes_requested: 'Ajustes solicitados',
        published: 'Publicado',
        closed: 'Encerrado',
        active: 'Ativo',
        completed: 'Concluído',
        cancelled: 'Cancelado',
        waiting: 'Lista de espera',
    }[status] || status || '-';
}

function openCourse(course) {
    selectedCourse.value = course;
    courseModal.value = true;
}

function openLesson(course) {
    selectedCourse.value = course;
    lessonForm.value = { title: '', content_type: 'youtube', content_url: '', duration_minutes: 30 };
    lessonModal.value = true;
}

function openScormImport() {
    scormError.value = '';
    scormForm.value = { category_id: store.categories[0]?.id || '', course_name: '', package: null };
    scormModal.value = true;
}

function selectScormFile(event) {
    scormForm.value.package = event.target.files?.[0] || null;
}

async function importScorm() {
    scormError.value = '';

    if (!scormForm.value.category_id || !scormForm.value.package) {
        scormError.value = 'Informe a categoria e selecione um pacote .zip.';
        return;
    }

    importingScorm.value = true;

    try {
        const course = await store.importScorm(scormForm.value);
        selectedCourse.value = course;
        scormModal.value = false;
        courseModal.value = true;
    } catch (error) {
        scormError.value = error.response?.data?.message || 'Não foi possível importar o pacote SCORM.';
    } finally {
        importingScorm.value = false;
    }
}

onMounted(async () => {
    if (!store.isAuthenticated) {
        router.push('/login');
        return;
    }
    await Promise.all([store.loadAdminCourses(), store.loadCategories()]);
});
</script>

<template>
    <DashboardShell
        area="Professor"
        title="Cursos e aulas"
        subtitle="Gerencie conteúdo, acompanhe alunos e publique atividades."
        icon="bi bi-person-video3"
        :items="sidebarItems"
        :user="store.user"
    >
        <template #actions>
            <button class="btn btn-outline-primary me-2" @click="openScormImport"><i class="bi bi-file-earmark-zip me-2"></i>Importar SCORM</button>
            <a href="/gestão/courses/create" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Novo curso</a>
        </template>

        <div class="panel table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Módulos</th>
                        <th>Situação</th>
                        <th>Professor</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="course in courses" :key="course.id">
                        <td><strong>{{ course.name }}</strong><br><small>{{ course.short_description }}</small></td>
                        <td>{{ course.modules_count ?? '-' }}</td>
                        <td><span class="badge text-bg-primary">{{ statusText(course.status) }}</span></td>
                        <td>{{ course.teacher?.name }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary me-2" @click="openCourse(course)"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-sm btn-outline-primary" @click="openLesson(course)"><i class="bi bi-file-earmark-plus"></i></button>
                        </td>
                    </tr>
                    <tr v-if="!courses.length">
                        <td colspan="5">
                            <EmptyState title="Nenhum curso encontrado" text="Crie um curso no painel de gestão para iniciar a trilha de aulas." />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <BaseModal :show="courseModal" title="Resumo do curso" @close="courseModal = false">
            <div v-if="selectedCourse" class="detail-grid">
                <div><span>Curso</span><strong>{{ selectedCourse.name }}</strong></div>
                <div><span>Situação</span><strong>{{ statusText(selectedCourse.status) }}</strong></div>
                <div><span>Categoria</span><strong>{{ selectedCourse.category?.name }}</strong></div>
                <div><span>Carga horária</span><strong>{{ selectedCourse.workload_hours }}h</strong></div>
                <p class="col-span">{{ selectedCourse.description || selectedCourse.short_description }}</p>
            </div>
            <template #footer>
                <button class="btn btn-outline-secondary" @click="courseModal = false">Fechar</button>
                <button class="btn btn-primary" @click="courseModal = false; openLesson(selectedCourse)">Nova aula</button>
            </template>
        </BaseModal>

        <BaseModal :show="scormModal" title="Importar pacote SCORM" @close="scormModal = false">
            <template #eyebrow><span class="modal-eyebrow">Curso automático em rascunho</span></template>
            <form id="scorm-form" @submit.prevent="importScorm">
                <div v-if="scormError" class="alert alert-danger">{{ scormError }}</div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select v-model="scormForm.category_id" class="form-select">
                        <option value="">Selecione</option>
                        <option v-for="category in store.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nome do curso</label>
                    <input v-model="scormForm.course_name" class="form-control" placeholder="Deixe vazio para usar o título do manifesto">
                </div>
                <div class="mb-3">
                    <label class="form-label">Pacote SCORM (.zip)</label>
                    <input class="form-control" type="file" accept=".zip,application/zip" @change="selectScormFile">
                </div>
                <p class="text-secondary mb-0">O sistema vai ler o imsmanifest.xml, criar o curso como rascunho e montar as aulas encontradas no pacote.</p>
            </form>
            <template #footer>
                <button class="btn btn-outline-secondary" :disabled="importingScorm" @click="scormModal = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="importingScorm" @click="importScorm">
                    <span v-if="importingScorm" class="spinner-border spinner-border-sm me-2"></span>
                    Importar pacote
                </button>
            </template>
        </BaseModal>

        <BaseModal :show="lessonModal" title="Nova aula" @close="lessonModal = false">
            <template #eyebrow><span class="modal-eyebrow">{{ selectedCourse?.name }}</span></template>
            <form id="lesson-form">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input v-model="lessonForm.title" class="form-control form-control-lg">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de conteúdo</label>
                        <select v-model="lessonForm.content_type" class="form-select">
                            <option value="youtube">YouTube</option>
                            <option value="vimeo">Vimeo</option>
                            <option value="mp4">MP4</option>
                            <option value="pdf">PDF</option>
                            <option value="external_link">Link externo</option>
                            <option value="scorm">SCORM</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duração</label>
                        <input v-model.number="lessonForm.duration_minutes" type="number" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">URL</label>
                        <input v-model="lessonForm.content_url" class="form-control">
                    </div>
                </div>
            </form>
            <template #footer>
                <button class="btn btn-outline-secondary" @click="lessonModal = false">Cancelar</button>
                <button class="btn btn-primary" @click="lessonModal = false"><i class="bi bi-save me-2"></i>Salvar aula</button>
            </template>
        </BaseModal>
    </DashboardShell>
</template>
