import { createRouter, createWebHistory } from 'vue-router';
import PortalHome from './views/PortalHome.vue';
import Courses from './views/Courses.vue';
import CourseDetail from './views/CourseDetail.vue';
import CertificateCheck from './views/CertificateCheck.vue';
import ChatForum from './views/ChatForum.vue';
import Login from './views/Login.vue';
import Contact from './views/Contact.vue';
import { useEadStore } from './stores/ead';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'portal', component: PortalHome },
        { path: '/cursos', name: 'courses', component: Courses },
        { path: '/cursos/:slug', name: 'course-detail', component: CourseDetail },
        { path: '/validar-certificado', name: 'certificate-check', component: CertificateCheck },
        { path: '/contato', name: 'contact', component: Contact },
        { path: '/comunicacao', name: 'communication', component: ChatForum, meta: { auth: true, roles: ['aluno', 'professor', 'administrador'], dashboardRedirect: true } },
        { path: '/login', name: 'login', component: Login },
    ],
});

router.beforeEach(async (to) => {
    const store = useEadStore();

    if (!to.meta.auth) {
        return true;
    }

    const user = await store.ensureSession();
    if (!user) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.dashboardRedirect) {
        window.location.href = store.role === 'professor' ? '/professor/forum' : '/aluno/comunicacao';
        return false;
    }

    const role = store.role;
    if (to.meta.roles?.length && !to.meta.roles.includes(role)) {
        if (role === 'administrador') {
            window.location.href = '/gestao';
            return false;
        }

        window.location.href = role === 'professor' ? '/professor' : '/aluno';
        return false;
    }

    return true;
});

export default router;
