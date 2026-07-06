<script setup>
import { onMounted, ref } from 'vue';

const isOpen = ref(false);
const fontStep = ref(0);

const classes = [
    'access-grayscale',
    'access-high-contrast',
    'access-negative-contrast',
    'access-underline-links',
    'access-readable-font',
];

function persist() {
    localStorage.setItem('accessibility_state', JSON.stringify({
        fontStep: fontStep.value,
        classes: classes.filter((className) => document.documentElement.classList.contains(className)),
    }));
}

function applyFontStep() {
    document.documentElement.style.setProperty('--access-font-scale', `${1 + fontStep.value * 0.08}`);
}

function toggleClass(className) {
    document.documentElement.classList.toggle(className);
    persist();
}

function reset() {
    fontStep.value = 0;
    classes.forEach((className) => document.documentElement.classList.remove(className));
    applyFontStep();
    persist();
}

function runAction(action) {
    if (action === 'increase-font') {
        fontStep.value = Math.min(fontStep.value + 1, 4);
        applyFontStep();
        persist();
        return;
    }

    if (action === 'decrease-font') {
        fontStep.value = Math.max(fontStep.value - 1, -2);
        applyFontStep();
        persist();
        return;
    }

    if (action === 'reset') {
        reset();
        return;
    }

    const map = {
        grayscale: 'access-grayscale',
        'high-contrast': 'access-high-contrast',
        'negative-contrast': 'access-negative-contrast',
        'underline-links': 'access-underline-links',
        'readable-font': 'access-readable-font',
    };

    if (map[action]) {
        toggleClass(map[action]);
    }
}

function onButtonKeydown(event) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        isOpen.value = !isOpen.value;
    }
}

onMounted(() => {
    const stored = JSON.parse(localStorage.getItem('accessibility_state') || 'null');

    if (stored) {
        fontStep.value = Number(stored.fontStep || 0);
        (stored.classes || []).forEach((className) => {
            if (classes.includes(className)) {
                document.documentElement.classList.add(className);
            }
        });
    }

    applyFontStep();
});
</script>

<template>
    <div
        id="accessibility-button"
        class="accessibility-button"
        role="button"
        tabindex="0"
        aria-label="Abrir menu de acessibilidade"
        aria-controls="accessibility-menu"
        :aria-expanded="isOpen"
        @click="isOpen = !isOpen"
        @keydown="onButtonKeydown"
    >
        <i class="bi bi-universal-access"></i>
    </div>

    <div
        id="accessibility-menu"
        class="accessibility-menu"
        :class="{ open: isOpen }"
        aria-label="Menu de Acessibilidade"
    >
        <div class="menu-header">
            <span>Menu de Acessibilidade</span>
            <button class="close-btn" type="button" aria-label="Fechar menu de acessibilidade" @click="isOpen = false">&times;</button>
        </div>
        <ul class="menu-list">
            <li data-accessibility-action="increase-font" @click="runAction('increase-font')"><i class="bi bi-zoom-in"></i> Aumentar Texto</li>
            <li data-accessibility-action="decrease-font" @click="runAction('decrease-font')"><i class="bi bi-zoom-out"></i> Diminuir Texto</li>
            <li data-accessibility-action="grayscale" @click="runAction('grayscale')"><i class="bi bi-palette"></i> Escala de Cinza</li>
            <li data-accessibility-action="high-contrast" @click="runAction('high-contrast')"><i class="bi bi-circle-half"></i> Alto Contraste</li>
            <li data-accessibility-action="negative-contrast" @click="runAction('negative-contrast')"><i class="bi bi-eye-slash"></i> Contraste Negativo</li>
            <li data-accessibility-action="underline-links" @click="runAction('underline-links')"><i class="bi bi-link-45deg"></i> Links Sublinhados</li>
            <li data-accessibility-action="readable-font" @click="runAction('readable-font')"><i class="bi bi-fonts"></i> Fonte Legivel</li>
            <li data-accessibility-action="reset" @click="runAction('reset')"><i class="bi bi-arrow-counterclockwise"></i> Redefinir</li>
        </ul>
    </div>
</template>
