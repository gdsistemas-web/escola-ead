<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const search = ref('');

const menu = [
    { label: 'Inicio', icon: 'ri-home-4-line', to: '/' },
    { label: 'Cursos', icon: 'ri-graduation-cap-line', to: '/cursos' },
    { label: 'Certificados', icon: 'ri-shield-check-line', to: '/validar-certificado' },
    { label: 'Contato', icon: 'ri-customer-service-2-line', to: '/contato' },
];

function submitSearch() {
    router.push({ path: '/cursos', query: search.value ? { busca: search.value } : {} });
}
</script>

<template>
    <header class="site-header" id="acessibilidade">
        <div class="topbar">
            <div class="container">
                <div class="topbar-left">
                    <a href="#conteudo"><strong>1</strong> Ir para o conteudo</a>
                    <span>|</span>
                    <a href="#menu-principal"><strong>2</strong> Ir para o menu</a>
                    <span>|</span>
                    <a href="#acessibilidade"><strong>3</strong> Acessibilidade do site</a>
                </div>
                <div class="topbar-right">
                    <a href="#mapa"><i class="ri-map-pin-line"></i> Mapa do site</a>
                    <a href="#lgpd"><i class="ri-shield-check-line"></i> LGPD</a>
                    <a href="/gestao"><i class="ri-lock-2-line"></i> Intranet</a>
                </div>
            </div>
        </div>

        <div class="brandbar">
            <div class="container">
                <RouterLink class="brand site-brand" to="/" aria-label="Inicio">
                    <img class="brand-logo brand-logo--school" :src="'/assets/logo_escola.png'" alt="Escola do Parlamento de Itapevi">
                </RouterLink>

                <form class="site-search" @submit.prevent="submitSearch">
                    <input v-model="search" type="search" class="form-control" placeholder="Pesquisar no site" aria-label="Pesquisar no site">
                    <button class="btn" type="submit" aria-label="Buscar">
                        <i class="ri-search-line"></i>
                    </button>
                </form>
            </div>
        </div>

        <nav class="mainmenu" id="menu-principal" aria-label="Menu principal">
            <div class="container">
                <ul>
                    <li v-for="item in menu" :key="item.label">
                        <RouterLink v-if="item.to" :to="item.to">
                            <i :class="item.icon"></i>
                            {{ item.label }}
                        </RouterLink>
                        <a v-else :href="item.href">
                            <i :class="item.icon"></i>
                            {{ item.label }}
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
</template>
