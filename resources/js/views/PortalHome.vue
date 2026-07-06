<script setup>
import { onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useEadStore } from '../stores/ead';

const store = useEadStore();
const { portal } = storeToRefs(store);

onMounted(() => store.loadPortal());
</script>

<template>
    <section class="institution-hero" id="quem-somos">
        <div class="container institution-hero-inner">
            <div class="hero-copy">
                <span>Escola do Parlamento de Itapevi</span>
                <h1>Formacao legislativa, cidadania e gestao publica em ambiente EAD.</h1>
                <p>Conheça os cursos ofertados, acompanhe publicações institucionais e acesse sua área de aprendizagem.</p>
                <div class="hero-actions">
                    <RouterLink to="/cursos" class="btn btn-primary btn-lg"><i class="bi bi-mortarboard me-2"></i>Ver cursos</RouterLink>
                    <RouterLink to="/login" class="btn btn-outline-success btn-lg"><i class="bi bi-person-circle me-2"></i>Area do aluno</RouterLink>
                </div>
            </div>
            <div class="hero-panel">
                <img :src="'/assets/logo_escola.png'" alt="Escola do Parlamento de Itapevi">
                <div class="hero-stats">
                    <div><strong>{{ portal.categories.length }}</strong><small>Categorias</small></div>
                    <div><strong>{{ portal.featured_courses.length }}</strong><small>Destaques</small></div>
                    <div><strong>{{ portal.news.length }}</strong><small>Noticias</small></div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="section-heading">
            <div>
                <span id="cursos">Cursos</span>
                <h2>Cursos em evidencia</h2>
            </div>
            <RouterLink to="/cursos" class="btn btn-outline-success btn-sm">Todos</RouterLink>
        </div>
        <div class="row g-3">
            <div v-for="course in portal.featured_courses" :key="course.id" class="col-md-6 col-xl-4">
                <article class="course-card h-100">
                    <span class="badge text-bg-success">{{ course.category?.name }}</span>
                    <h3 class="h5 mt-3">{{ course.name }}</h3>
                    <p>{{ course.short_description }}</p>
                    <div class="d-flex justify-content-between small text-secondary">
                        <span><i class="bi bi-clock me-1"></i>{{ course.workload_hours }}h</span>
                        <span>{{ course.teacher?.name }}</span>
                    </div>
                </article>
            </div>
            <div v-if="!portal.featured_courses.length" class="col-12">
                <div class="empty-featured">
                    <i class="bi bi-mortarboard"></i>
                    <strong>Nenhum curso em destaque ainda</strong>
                    <span>Marque cursos publicados como destaque no painel de gestao.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="info-band" id="biblioteca">
        <div class="container info-grid">
            <article>
                <i class="bi bi-book"></i>
                <h2>Biblioteca</h2>
                <p>Materiais de apoio, publicacoes e conteudos complementares para a formacao legislativa.</p>
            </article>
            <article id="docentes">
                <i class="bi bi-people"></i>
                <h2>Docentes</h2>
                <p>Professores e instrutores vinculados aos cursos da Escola do Parlamento.</p>
            </article>
            <article id="agenda">
                <i class="bi bi-calendar-event"></i>
                <h2>Agenda</h2>
                <p>Programacao de cursos, turmas, eventos e atividades institucionais.</p>
            </article>
        </div>
    </section>
</template>
