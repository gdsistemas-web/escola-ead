<template>
    <div>
        <SiteHeader v-if="showPublicChrome" />
        <main id="conteudo">
            <RouterView />
        </main>
        <SiteFooter v-if="showPublicChrome" />
        <AccessibilityTools v-if="showPublicChrome" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AccessibilityTools from './AccessibilityTools.vue';
import SiteFooter from './SiteFooter.vue';
import SiteHeader from './SiteHeader.vue';

const route = useRoute();
const publicRoutes = ['portal', 'courses', 'course-detail', 'certificate-check', 'contact'];
const showPublicChrome = computed(() => publicRoutes.includes(route.name));
</script>
