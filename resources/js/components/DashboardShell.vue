<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    area: { type: String, required: true },
    icon: { type: String, default: 'bi bi-grid' },
    items: { type: Array, default: () => [] },
    user: { type: Object, default: null },
});

const emit = defineEmits(['action']);
const router = useRouter();

const groupedItems = computed(() => props.items.reduce((groups, item) => {
    const group = item.group || 'Navegacao';
    groups[group] = groups[group] || [];
    groups[group].push(item);
    return groups;
}, {}));

function navigate(item) {
    if (!item.to) {
        emit('action', item);
        return;
    }

    if (item.to.startsWith('/gestao')) {
        window.location.href = item.to;
        return;
    }

    router.push(item.to);
}
</script>

<template>
    <section class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <RouterLink to="/" class="sidebar-brand">
                <span class="brand-mark">EP</span>
                <span>
                    <strong>EPI EAD</strong>
                    <small>Escola do Parlamento</small>
                </span>
            </RouterLink>

            <nav class="sidebar-nav" aria-label="Navegacao da area">
                <section v-for="(groupItems, group) in groupedItems" :key="group" class="sidebar-group">
                    <h2>{{ group }}</h2>
                    <button
                        v-for="item in groupItems"
                        :key="item.label"
                        class="sidebar-link"
                        :class="{ active: item.active }"
                        type="button"
                        @click="navigate(item)"
                    >
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                        <small v-if="item.badge">{{ item.badge }}</small>
                    </button>
                </section>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="avatar">{{ user?.name?.slice(0, 2).toUpperCase() || 'EP' }}</div>
                    <div>
                        <strong>{{ user?.name || 'Usuario EPI' }}</strong>
                        <small>{{ user?.email || 'sessao ativa' }}</small>
                    </div>
                </div>
                <slot name="sidebar-footer"></slot>
            </div>
        </aside>

        <div class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <span class="modal-eyebrow"><i :class="icon" class="me-1"></i>{{ area }}</span>
                    <h1 class="h3 mb-1">{{ title }}</h1>
                    <p v-if="subtitle" class="text-secondary mb-0">{{ subtitle }}</p>
                </div>
                <div class="topbar-actions">
                    <slot name="actions"></slot>
                </div>
            </header>

            <div class="dashboard-content">
                <slot></slot>
            </div>
        </div>
    </section>
</template>
