<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useEadStore } from '../stores/ead';
import BaseModal from '../components/BaseModal.vue';
import EmptyState from '../components/EmptyState.vue';

const router = useRouter();
const route = useRoute();
const store = useEadStore();
const { courses, portal } = storeToRefs(store);
const search = ref(String(route.query.busca || ''));
const category = ref(String(route.query.categoria || 'all'));
const workload = ref('all');
const order = ref('recent');
const message = ref('');
const selectedCourse = ref(null);
const enrollModal = ref(false);
const successModal = ref(false);
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

const categories = computed(() => portal.value.categories?.length
    ? portal.value.categories
    : [...new Map(courses.value.filter((course) => course.category).map((course) => [course.category.id, course.category])).values()]
);

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    const result = courses.value.filter((course) => {
        const matchesSearch = !term
            || course.name.toLowerCase().includes(term)
            || (course.short_description || '').toLowerCase().includes(term)
            || (course.category?.name || '').toLowerCase().includes(term);
        const matchesCategory = category.value === 'all' || String(course.category_id) === category.value || String(course.category?.id) === category.value;
        const hours = Number(course.workload_hours || 0);
        const matchesWorkload = workload.value === 'all'
            || (workload.value === 'short' && hours <= 20)
            || (workload.value === 'medium' && hours > 20 && hours <= 60)
            || (workload.value === 'long' && hours > 60);

        return matchesSearch && matchesCategory && matchesWorkload;
    });

    return result.sort((a, b) => {
        if (order.value === 'name') {
            return a.name.localeCompare(b.name);
        }

        if (order.value === 'workload') {
            return Number(a.workload_hours || 0) - Number(b.workload_hours || 0);
        }

        return Number(b.id) - Number(a.id);
    });
});

async function enroll(course) {
    if (!store.isAuthenticated) {
        router.push({ name: 'login', query: { redirect: `/cursos/${course.slug}` } });
        return;
    }

    await store.ensureSession();
    enrollmentForm.value.document = store.user?.profile?.document || enrollmentForm.value.document;
    enrollmentForm.value.phone = store.user?.profile?.phone || enrollmentForm.value.phone;
    enrollmentForm.value.birthdate = store.user?.profile?.birthdate || enrollmentForm.value.birthdate;
    enrollmentForm.value.city = store.user?.profile?.city || enrollmentForm.value.city;
    enrollmentForm.value.state = store.user?.profile?.state || enrollmentForm.value.state || 'SP';
    enrollmentForm.value.accept_terms = false;
    enrollmentError.value = '';
    selectedCourse.value = course;
    enrollModal.value = true;
}

async function confirmEnroll() {
    enrollmentError.value = '';
    enrolling.value = true;

    try {
        enrollment.value = await store.enroll(selectedCourse.value, enrollmentForm.value);
        const protocol = enrollment.value?.application_data?.protocol;
        message.value = `Matrícula registrada em ${selectedCourse.value.name}.${protocol ? ` Protocolo: ${protocol}.` : ''}`;
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
    await Promise.all([store.loadCourses(), store.loadPortal()]);
});

watch(() => route.query.busca, (value) => {
    search.value = String(value || '');
});
</script>

<template>
    <section class="container py-5">
        <div class="catalog-hero mb-4">
            <div>
                <span class="section-kicker">Cursos EAD EPI</span>
                <h1 class="h2 mb-2">Catálogo de cursos</h1>
                <p class="text-secondary mb-0">Encontre formações publicadas, confira requisitos e avance para a matrícula.</p>
            </div>
            <RouterLink class="btn btn-outline-primary" to="/validar-certificado" aria-label="Abrir validação pública de certificado">
                <i class="bi bi-patch-check me-2"></i>Validar certificado
            </RouterLink>
        </div>

        <div class="catalog-toolbar mb-4" aria-label="Filtros do catálogo">
            <label class="catalog-field">
                <span>Buscar</span>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input v-model="search" class="form-control" placeholder="Nome, tema ou categoria">
                </div>
            </label>
            <label class="catalog-field">
                <span>Categoria</span>
                <select v-model="category" class="form-select">
                    <option value="all">Todas</option>
                    <option v-for="item in categories" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
            </label>
            <label class="catalog-field">
                <span>Carga horária</span>
                <select v-model="workload" class="form-select">
                    <option value="all">Todas</option>
                    <option value="short">Até 20h</option>
                    <option value="medium">21h a 60h</option>
                    <option value="long">Acima de 60h</option>
                </select>
            </label>
            <label class="catalog-field">
                <span>Ordenar</span>
                <select v-model="order" class="form-select">
                    <option value="recent">Mais recentes</option>
                    <option value="name">Nome</option>
                    <option value="workload">Carga horária</option>
                </select>
            </label>
        </div>

        <div v-if="message" class="alert alert-success">{{ message }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>{{ filtered.length }} curso(s) encontrado(s)</strong>
            <button class="btn btn-sm btn-outline-secondary" @click="search = ''; category = 'all'; workload = 'all'; order = 'recent'">
                <i class="bi bi-x-circle me-1"></i>Limpar filtros
            </button>
        </div>

        <div class="row g-3">
            <div v-for="course in filtered" :key="course.id" class="col-md-6 col-xl-4">
                <article class="course-card catalog-course-card h-100">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="badge text-bg-light border">{{ course.category?.name || 'Curso' }}</span>
                        <span class="badge text-bg-success">Publicado</span>
                    </div>
                    <h2 class="h5 mt-3">{{ course.name }}</h2>
                    <p>{{ course.short_description }}</p>
                    <div class="course-meta-list">
                        <span><i class="bi bi-clock"></i>{{ course.workload_hours }}h</span>
                        <span><i class="bi bi-award"></i>Nota {{ course.minimum_grade }}</span>
                        <span><i class="bi bi-graph-up-arrow"></i>{{ course.minimum_progress_percent }}% mínimo</span>
                    </div>
                    <div class="teacher-line">
                        <i class="bi bi-person-workspace"></i>
                        <span>{{ course.teacher?.name || 'Equipe EAD EPI' }}</span>
                    </div>
                    <div class="d-grid gap-2 mt-auto">
                        <RouterLink class="btn btn-primary" :to="`/cursos/${course.slug}`">
                            <i class="bi bi-window-stack me-2"></i>Ver curso
                        </RouterLink>
                        <button class="btn btn-outline-secondary" @click="enroll(course)">
                            <i class="bi bi-person-plus me-2"></i>Matricular-se
                        </button>
                    </div>
                </article>
            </div>
            <div v-if="!filtered.length" class="col-12">
                <EmptyState title="Nenhum curso publicado encontrado" text="Ajuste os filtros ou retorne ao catálogo completo." />
            </div>
        </div>

        <BaseModal :show="enrollModal" title="Formulário de matrícula" size="modal-lg" @close="enrollModal = false">
            <p class="mb-2">Você está se matriculando em:</p>
            <div class="selection-card">
                <strong>{{ selectedCourse?.name }}</strong>
                <span>{{ selectedCourse?.workload_hours }}h - {{ selectedCourse?.teacher?.name }}</span>
            </div>

            <div v-if="enrollmentError" class="alert alert-danger mt-3 mb-0">{{ enrollmentError }}</div>

            <form class="enrollment-form mt-3" @submit.prevent="confirmEnroll">
                <div class="form-section-title">Dados pessoais</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-document">CPF/documento</label>
                        <input id="catalog-document" v-model="enrollmentForm.document" class="form-control" required maxlength="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-phone">Telefone</label>
                        <input id="catalog-phone" v-model="enrollmentForm.phone" class="form-control" required maxlength="30">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-birthdate">Nascimento</label>
                        <input id="catalog-birthdate" v-model="enrollmentForm.birthdate" type="date" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="catalog-city">Cidade</label>
                        <input id="catalog-city" v-model="enrollmentForm.city" class="form-control" required maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-state">UF</label>
                        <input id="catalog-state" v-model="enrollmentForm.state" class="form-control text-uppercase" required maxlength="2">
                    </div>
                </div>

                <div class="form-section-title mt-4">Perfil acadêmico/profissional</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-education">Escolaridade</label>
                        <select id="catalog-education" v-model="enrollmentForm.education_level" class="form-select">
                            <option value="">Selecione</option>
                            <option>Ensino médio</option>
                            <option>Ensino técnico</option>
                            <option>Graduação</option>
                            <option>Pós-graduação</option>
                            <option>Servidor público</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-occupation">Ocupação</label>
                        <input id="catalog-occupation" v-model="enrollmentForm.occupation" class="form-control" maxlength="120">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="catalog-institution">Instituição/órgão</label>
                        <input id="catalog-institution" v-model="enrollmentForm.institution" class="form-control" maxlength="160">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="catalog-motivation">Por que deseja participar?</label>
                        <textarea id="catalog-motivation" v-model="enrollmentForm.motivation" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="catalog-accessibility">Necessidade de acessibilidade ou observação</label>
                        <textarea id="catalog-accessibility" v-model="enrollmentForm.accessibility_needs" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                </div>

                <label class="lgpd-label mt-3">
                    <input v-model="enrollmentForm.accept_terms" type="checkbox" class="lgpd-checkbox" required>
                    <span>Declaro que as informações são verdadeiras e autorizo o uso dos dados para gestão acadêmica, comunicação e emissão de certificado.</span>
                </label>
            </form>
            <template #footer>
                <button class="btn btn-outline-secondary" :disabled="enrolling" @click="enrollModal = false">Cancelar</button>
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
