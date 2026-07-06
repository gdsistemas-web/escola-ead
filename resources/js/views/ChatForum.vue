<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEadStore } from '../stores/ead';
import BaseModal from '../components/BaseModal.vue';
import EmptyState from '../components/EmptyState.vue';

const route = useRoute();
const router = useRouter();
const store = useEadStore();

const topics = ref([]);
const categories = ref([]);
const tags = ref([]);
const ranking = ref([]);
const notifications = ref([]);
const activeTopic = ref(null);
const topicModal = ref(false);
const darkMode = ref(localStorage.getItem('forum_theme') === 'dark');
const loading = ref(false);
const search = ref('');
const filters = reactive({ course_id: '', status: '', tag: '' });
const topicForm = reactive({
    forum_category_id: '',
    title: '',
    body: '',
    type: 'discussion',
    tags: [],
    is_assessment: false,
    requires_reply: false,
    assessment_points: null,
    assessment_due_at: '',
});
const replyForm = reactive({ body: '', attachments: '' });

const canModerate = computed(() => ['administrador', 'professor'].includes(store.role));
const statuses = [
    { value: '', label: 'Todos' },
    { value: 'open', label: 'Aberto' },
    { value: 'resolved', label: 'Resolvido' },
    { value: 'pinned', label: 'Fixado' },
    { value: 'closed', label: 'Encerrado' },
];

function statusText(status) {
    return {
        open: 'Aberto',
        resolved: 'Resolvido',
        pinned: 'Fixado',
        closed: 'Encerrado',
        hidden: 'Oculto',
    }[status] || status || 'Aberto';
}
const reactions = [
    { value: 'liked', label: 'Gostei', icon: 'ri-thumb-up-line' },
    { value: 'useful', label: 'Util', icon: 'ri-heart-3-line' },
    { value: 'excellent', label: 'Excelente', icon: 'ri-graduation-cap-line' },
];

async function loadForum() {
    loading.value = true;
    const params = {
        search: search.value || undefined,
        course_id: filters.course_id || undefined,
        status: filters.status || undefined,
        tag: filters.tag || undefined,
    };
    const [topicResponse, categoryResponse, tagResponse, rankingResponse, notificationResponse] = await Promise.all([
        axios.get('/api/forums', { params }),
        axios.get('/api/forum-categories'),
        axios.get('/api/forum-tags'),
        axios.get('/api/forum-ranking', { params: { period: 'month', course_id: filters.course_id || undefined } }),
        axios.get('/api/forum-notifications'),
    ]);
    topics.value = topicResponse.data.data ?? topicResponse.data;
    categories.value = categoryResponse.data;
    tags.value = tagResponse.data;
    ranking.value = rankingResponse.data;
    notifications.value = notificationResponse.data.data ?? notificationResponse.data;
    topicForm.forum_category_id = topicForm.forum_category_id || categories.value[0]?.id || '';
    loading.value = false;

    if (route.query.topic) {
        await openTopic(route.query.topic);
    } else if (!activeTopic.value && topics.value.length) {
        await openTopic(topics.value[0].id);
    }
}

async function openTopic(topicId) {
    const { data } = await axios.get(`/api/forums/${topicId}`);
    activeTopic.value = data;
    router.replace({ path: '/comunicacao', query: { topic: topicId } });
}

function insertMarkup(kind) {
    const wrappers = {
        bold: ['**', '**'],
        quote: ['> ', ''],
        link: ['[texto](', ')'],
        mention: ['@', ''],
        image: ['![descricao](', ')'],
        pdf: ['[PDF](', ')'],
    };
    const [before, after] = wrappers[kind];
    replyForm.body = `${replyForm.body}${before}${after}`;
}

async function createTopic() {
    const payload = {
        ...topicForm,
        is_assessment: topicForm.type === 'assessment' || topicForm.is_assessment,
        tags: topicForm.tags,
    };
    const { data } = await axios.post('/api/forums', payload);
    topics.value.unshift(data);
    topicModal.value = false;
    Object.assign(topicForm, {
        forum_category_id: categories.value[0]?.id || '',
        title: '',
        body: '',
        type: 'discussion',
        tags: [],
        is_assessment: false,
        requires_reply: false,
        assessment_points: null,
        assessment_due_at: '',
    });
    await openTopic(data.id);
    await loadForum();
}

async function reply() {
    if (!activeTopic.value || !replyForm.body.trim()) return;
    const attachments = replyForm.attachments
        ? replyForm.attachments.split('\n').map((url) => url.trim()).filter(Boolean)
        : [];
    const { data } = await axios.post(`/api/forums/${activeTopic.value.id}/replies`, { body: replyForm.body, attachments });
    activeTopic.value.replies = [...(activeTopic.value.replies || []), data];
    replyForm.body = '';
    replyForm.attachments = '';
    await loadForum();
}

async function react(reaction, replyId = null) {
    await axios.post(`/api/forums/${activeTopic.value.id}/react`, { reaction, forum_reply_id: replyId });
    await openTopic(activeTopic.value.id);
}

async function markBestAnswer(replyId) {
    await axios.post(`/api/forums/${activeTopic.value.id}/best-answer/${replyId}`);
    await openTopic(activeTopic.value.id);
    await loadForum();
}

async function subscribe() {
    await axios.post(`/api/forums/${activeTopic.value.id}/subscribe`);
}

async function updateStatus(status) {
    await axios.put(`/api/forums/${activeTopic.value.id}`, {
        status,
        is_pinned: status === 'pinned',
        is_closed: status === 'closed',
    });
    await openTopic(activeTopic.value.id);
    await loadForum();
}

async function hideReply(replyId) {
    await axios.post(`/api/forum-replies/${replyId}/hide`);
    await openTopic(activeTopic.value.id);
}

function toggleTheme() {
    darkMode.value = !darkMode.value;
    localStorage.setItem('forum_theme', darkMode.value ? 'dark' : 'light');
}

onMounted(async () => {
    if (!store.isAuthenticated) {
        router.push('/login');
        return;
    }
    await loadForum();
});

watch(() => [filters.status, filters.tag, filters.course_id], loadForum);
</script>

<template>
    <section class="academic-forum" :class="{ 'forum-dark': darkMode }">
        <aside class="forum-sidebar">
            <div class="forum-sidebar-header">
                <div>
                    <span>Comunidade EAD</span>
                    <h1>Fórum acadêmico</h1>
                </div>
                <button class="icon-button" type="button" @click="toggleTheme" :aria-label="darkMode ? 'Ativar modo claro' : 'Ativar modo escuro'">
                    <i :class="darkMode ? 'ri-sun-line' : 'ri-moon-line'"></i>
                </button>
            </div>

            <div class="forum-search">
                <i class="ri-search-line"></i>
                <input v-model="search" type="search" placeholder="Buscar por titulo, conteudo ou usuario" @keyup.enter="loadForum">
            </div>

            <div class="forum-filters">
                <select v-model="filters.status">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
                <select v-model="filters.tag">
                    <option value="">Todas as tags</option>
                    <option v-for="tag in tags" :key="tag.id" :value="tag.slug">{{ tag.name }}</option>
                </select>
            </div>

            <button class="btn btn-primary w-100 mb-3" @click="topicModal = true">
                <i class="ri-add-line me-1"></i>Novo tópico
            </button>

            <div class="topic-list">
                <button
                    v-for="topic in topics"
                    :key="topic.id"
                    class="forum-topic-button"
                    :class="{ active: activeTopic?.id === topic.id }"
                    @click="openTopic(topic.id)"
                >
                    <span class="topic-status" :data-status="topic.status">{{ statusText(topic.status) }}</span>
                    <strong>{{ topic.title }}</strong>
                    <small>{{ topic.author?.name }} · {{ topic.replies_count ?? topic.replies?.length ?? 0 }} respostas · {{ topic.views_count }} views</small>
                    <span class="topic-tags">
                        <span v-for="tag in topic.tags" :key="tag.id">{{ tag.name }}</span>
                    </span>
                </button>
                <EmptyState v-if="!topics.length && !loading" title="Nenhum tópico encontrado" text="Abra uma pergunta, debate ou comunicado acadêmico." />
            </div>
        </aside>

        <main class="forum-thread">
            <div v-if="activeTopic" class="thread-card">
                <div class="thread-header">
                    <div>
                        <div class="thread-meta">
                            <span>{{ activeTopic.category?.name }}</span>
                            <span v-if="activeTopic.course">{{ activeTopic.course.name }}</span>
                            <span v-if="activeTopic.lesson">{{ activeTopic.lesson.title }}</span>
                        </div>
                        <h2>{{ activeTopic.title }}</h2>
                    </div>
                    <div class="thread-actions">
                        <button class="btn btn-outline-primary btn-sm" @click="subscribe"><i class="ri-notification-3-line me-1"></i>Acompanhar</button>
                        <div v-if="canModerate" class="btn-group">
                            <button class="btn btn-outline-secondary btn-sm" @click="updateStatus('pinned')">Fixar</button>
                            <button class="btn btn-outline-secondary btn-sm" @click="updateStatus('resolved')">Resolver</button>
                            <button class="btn btn-outline-danger btn-sm" @click="updateStatus('closed')">Encerrar</button>
                        </div>
                    </div>
                </div>

                <article class="forum-post">
                    <div class="author-avatar">{{ activeTopic.author?.name?.slice(0, 2) }}</div>
                    <div>
                        <div class="post-author">
                            <strong>{{ activeTopic.author?.name }}</strong>
                            <span>{{ statusText(activeTopic.status) }}</span>
                        </div>
                        <p>{{ activeTopic.body }}</p>
                        <div class="reaction-row">
                            <button v-for="reaction in reactions" :key="reaction.value" @click="react(reaction.value)">
                                <i :class="reaction.icon"></i>{{ reaction.label }}
                            </button>
                        </div>
                    </div>
                </article>

                <article v-if="activeTopic.accepted_reply" class="best-answer">
                    <i class="ri-checkbox-circle-fill"></i>
                    <div>
                        <strong>Melhor resposta</strong>
                        <p>{{ activeTopic.accepted_reply.body }}</p>
                        <small>{{ activeTopic.accepted_reply.author?.name }}</small>
                    </div>
                </article>

                <div class="reply-list">
                    <article v-for="replyItem in activeTopic.replies" :key="replyItem.id" class="forum-post" :class="{ accepted: replyItem.is_accepted }">
                        <div class="author-avatar">{{ replyItem.author?.name?.slice(0, 2) }}</div>
                        <div>
                            <div class="post-author">
                                <strong>{{ replyItem.author?.name }}</strong>
                                <span v-if="replyItem.is_accepted">Melhor resposta</span>
                            </div>
                            <p>{{ replyItem.body }}</p>
                            <div v-if="replyItem.attachments?.length" class="attachment-list">
                                <a v-for="attachment in replyItem.attachments" :key="attachment" :href="attachment" target="_blank" rel="noopener">
                                    <i class="ri-attachment-2"></i>{{ attachment }}
                                </a>
                            </div>
                            <div class="reaction-row">
                                <button v-for="reaction in reactions" :key="reaction.value" @click="react(reaction.value, replyItem.id)">
                                    <i :class="reaction.icon"></i>{{ reaction.label }}
                                </button>
                                <button v-if="canModerate && !replyItem.is_accepted" @click="markBestAnswer(replyItem.id)">
                                    <i class="ri-checkbox-circle-line"></i>Melhor resposta
                                </button>
                                <button v-if="canModerate" @click="hideReply(replyItem.id)">
                                    <i class="ri-eye-off-line"></i>Ocultar
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="reply-composer">
                    <div class="editor-toolbar">
                        <button type="button" @click="insertMarkup('bold')"><i class="ri-bold"></i></button>
                        <button type="button" @click="insertMarkup('quote')"><i class="ri-double-quotes-l"></i></button>
                        <button type="button" @click="insertMarkup('mention')"><i class="ri-at-line"></i></button>
                        <button type="button" @click="insertMarkup('link')"><i class="ri-link"></i></button>
                        <button type="button" @click="insertMarkup('image')"><i class="ri-image-line"></i></button>
                        <button type="button" @click="insertMarkup('pdf')"><i class="ri-file-pdf-2-line"></i></button>
                    </div>
                    <textarea v-model="replyForm.body" rows="5" placeholder="Escreva uma resposta, mencione @usuario, cite fontes e contribua com a turma."></textarea>
                    <textarea v-model="replyForm.attachments" rows="2" placeholder="Links de imagens, PDFs ou materiais, um por linha."></textarea>
                    <button class="btn btn-primary" @click="reply"><i class="ri-send-plane-line me-1"></i>Responder</button>
                </div>
            </div>
            <EmptyState v-else title="Selecione um tópico" text="Escolha uma discussão para ler respostas, reagir ou participar." />
        </main>

        <aside class="forum-insights">
            <section class="insight-card">
                <h2>Top participantes do mes</h2>
                <div v-for="item in ranking" :key="item.user_id" class="ranking-row">
                    <span>{{ item.user?.name }}</span>
                    <strong>{{ item.reputation }} pts</strong>
                </div>
                <EmptyState v-if="!ranking.length" title="Sem ranking ainda" text="A participacao dos alunos aparecera aqui." />
            </section>

            <section class="insight-card">
                <h2>Notificacoes</h2>
                <div v-for="notification in notifications.slice(0, 6)" :key="notification.id" class="notification-row">
                    <strong>{{ notification.title }}</strong>
                    <span>{{ notification.body }}</span>
                </div>
                <EmptyState v-if="!notifications.length" title="Nada novo" text="Mencoes, curtidas e respostas aparecerao aqui." />
            </section>
        </aside>

        <BaseModal :show="topicModal" title="Novo tópico acadêmico" @close="topicModal = false">
            <form id="forum-topic-form" @submit.prevent="createTopic">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Categoria</label>
                        <select v-model="topicForm.forum_category_id" class="form-select" required>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }} · {{ category.course?.name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Tipo</label>
                        <select v-model="topicForm.type" class="form-select">
                            <option value="discussion">Debate</option>
                            <option value="question">Duvida</option>
                            <option value="announcement">Comunicado</option>
                            <option v-if="canModerate" value="assessment">Avaliativo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Titulo</label>
                        <input v-model="topicForm.title" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Conteudo</label>
                        <textarea v-model="topicForm.body" class="form-control" rows="6" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tags</label>
                        <div class="tag-picker">
                            <label v-for="tag in tags" :key="tag.id">
                                <input v-model="topicForm.tags" type="checkbox" :value="tag.name">
                                {{ tag.name }}
                            </label>
                        </div>
                    </div>
                    <template v-if="canModerate && topicForm.type === 'assessment'">
                        <div class="col-md-4">
                            <label class="form-label">Valor em pontos</label>
                            <input v-model.number="topicForm.assessment_points" type="number" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prazo</label>
                            <input v-model="topicForm.assessment_due_at" type="datetime-local" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <label class="form-check">
                                <input v-model="topicForm.requires_reply" class="form-check-input" type="checkbox">
                                <span class="form-check-label">Resposta obrigatoria</span>
                            </label>
                        </div>
                    </template>
                </div>
            </form>
            <template #footer>
                <button class="btn btn-outline-secondary" @click="topicModal = false">Cancelar</button>
                <button class="btn btn-primary" form="forum-topic-form">Publicar</button>
            </template>
        </BaseModal>
    </section>
</template>
