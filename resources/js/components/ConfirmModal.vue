<script setup>
import BaseModal from './BaseModal.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar acao' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmar' },
    tone: { type: String, default: 'danger' },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <BaseModal :show="show" :title="title" size="modal-sm" @close="$emit('close')">
        <p class="text-secondary mb-0">{{ message }}</p>
        <template #footer>
            <button class="btn btn-outline-secondary" @click="$emit('close')">Cancelar</button>
            <button class="btn" :class="`btn-${tone}`" @click="$emit('confirm')">{{ confirmLabel }}</button>
        </template>
    </BaseModal>
</template>
