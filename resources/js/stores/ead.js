import { defineStore } from 'pinia';
import axios from 'axios';

const token = localStorage.getItem('ead_token');
if (token) {
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
}

export const useEadStore = defineStore('ead', {
    state: () => ({
        token,
        user: JSON.parse(localStorage.getItem('ead_user') || 'null'),
        sessionChecked: false,
        portal: { banners: [], featured_courses: [], news: [], faq: [], categories: [] },
        courses: [],
        categories: [],
        teachers: [],
        enrollments: [],
        dashboard: null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        role: (state) => state.user?.roles?.[0]?.name,
    },
    actions: {
        setSession(payload) {
            this.token = payload.token;
            this.user = payload.user;
            this.sessionChecked = true;
            localStorage.setItem('ead_token', payload.token);
            localStorage.setItem('ead_user', JSON.stringify(payload.user));
            axios.defaults.headers.common.Authorization = `Bearer ${payload.token}`;
        },
        clearSession() {
            this.token = null;
            this.user = null;
            this.sessionChecked = true;
            localStorage.removeItem('ead_token');
            localStorage.removeItem('ead_user');
            delete axios.defaults.headers.common.Authorization;
        },
        async ensureSession() {
            if (!this.token) {
                this.clearSession();
                return null;
            }

            try {
                const { data } = await axios.get('/api/auth/me');
                this.user = data;
                this.sessionChecked = true;
                localStorage.setItem('ead_user', JSON.stringify(data));
                return data;
            } catch (exception) {
                this.clearSession();
                return null;
            }
        },
        async login(credentials) {
            this.error = null;
            const { data } = await axios.post('/api/auth/login', credentials);
            this.setSession(data);
            return data;
        },
        async register(payload) {
            this.error = null;
            const { data } = await axios.post('/api/auth/register', payload);
            this.setSession(data);
            return data;
        },
        async logout() {
            if (this.token) {
                await axios.post('/api/auth/logout').catch(() => null);
            }
            this.clearSession();
        },
        async loadPortal() {
            this.loading = true;
            const { data } = await axios.get('/api/portal');
            this.portal = data;
            this.loading = false;
        },
        async loadCourses() {
            const { data } = await axios.get('/api/portal/courses');
            this.courses = data.data ?? data;
        },
        async loadPublicCourse(slug) {
            const { data } = await axios.get(`/api/portal/courses/${slug}`);
            return data;
        },
        async loadAdminCourses() {
            const { data } = await axios.get('/api/courses');
            this.courses = data.data ?? data;
        },
        async loadCategories() {
            const { data } = await axios.get('/api/courses/categories');
            this.categories = data;
        },
        async loadTeachers() {
            const { data } = await axios.get('/api/users?role=professor');
            this.teachers = data.data ?? data;
        },
        async loadDashboard() {
            const { data } = await axios.get('/api/dashboard');
            this.dashboard = data;
        },
        async saveCourse(course) {
            const payload = {
                ...course,
                work_load_hours: undefined,
                is_featured: Boolean(course.is_featured),
            };

            if (course.id) {
                const { data } = await axios.put(`/api/courses/${course.id}`, payload);
                return data;
            }

            const { data } = await axios.post('/api/courses', payload);
            return data;
        },
        async deleteCourse(course) {
            await axios.delete(`/api/courses/${course.id}`);
            await this.loadAdminCourses();
        },
        async enroll(course, application = {}) {
            const { data } = await axios.post('/api/enrollments', { course_id: course.id, ...application });
            await this.loadEnrollments();
            return data;
        },
        async issueCertificate(course) {
            const { data } = await axios.post('/api/certificates', { course_id: course.id });
            return data;
        },
        async loadEnrollments() {
            const { data } = await axios.get('/api/enrollments');
            this.enrollments = data.data ?? data;
        },
        async loadEnrollment(enrollment) {
            const { data } = await axios.get(`/api/enrollments/${enrollment.id}`);
            return data;
        },
        async saveLessonProgress(lesson, payload) {
            const { data } = await axios.post(`/api/lessons/${lesson.id}/progress`, payload);
            return data;
        },
        async importScorm(payload) {
            const form = new FormData();
            form.append('category_id', payload.category_id);
            if (payload.course_name) {
                form.append('course_name', payload.course_name);
            }
            form.append('package', payload.package);

            const { data } = await axios.post('/api/scorm/import', form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            await this.loadAdminCourses();
            return data;
        },
        async loadScormLaunch(lesson) {
            const { data } = await axios.get(`/api/lessons/${lesson.id}/scorm/launch`);
            return data;
        },
        async commitScorm(lesson, values, finished = false) {
            const { data } = await axios.post(`/api/lessons/${lesson.id}/scorm/commit`, { values, finished });
            return data;
        },
        async loadExam(exam) {
            const { data } = await axios.get(`/api/exams/${exam.id}`);
            return data;
        },
        async submitExam(exam, answers) {
            const { data } = await axios.post(`/api/exams/${exam.id}/submit`, { answers });
            return data;
        },
    },
});
