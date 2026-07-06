<script setup>
defineProps({
    icon: { type: String, default: 'bi bi-inbox' },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <div class="empty-state">
        <i :class="icon"></i>
        <strong>{{ title }}</strong>
        <span>{{ text }}</span>
        <slot></slot>
    </div>
</template>
