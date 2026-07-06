<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'modal-lg' },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="modal-stack" @keydown.esc="$emit('close')">
            <div class="modal-backdrop-custom" @click="$emit('close')"></div>
            <section class="modal-shell" :class="size" role="dialog" aria-modal="true" :aria-label="title">
                <header class="modal-head">
                    <div>
                        <slot name="eyebrow"></slot>
                        <h2 class="h5 mb-0">{{ title }}</h2>
                    </div>
                    <button class="icon-btn" type="button" aria-label="Fechar" @click="$emit('close')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </header>
                <div class="modal-body-custom">
                    <slot></slot>
                </div>
                <footer v-if="$slots.footer" class="modal-foot">
                    <slot name="footer"></slot>
                </footer>
            </section>
        </div>
    </Teleport>
</template>
