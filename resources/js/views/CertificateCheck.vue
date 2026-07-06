<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';

const code = ref('');
const loading = ref(false);
const error = ref('');
const certificate = ref(null);

const statusLabel = computed(() => certificate.value?.valid ? 'Certificado válido' : 'Certificado revogado');

function statusText(status) {
    return {
        valid: 'Válido',
        revoked: 'Revogado',
    }[status] || status || '-';
}

async function validateCertificate() {
    const query = code.value.trim();
    if (!query) {
        error.value = 'Informe o código ou hash do certificado.';
        certificate.value = null;
        return;
    }

    loading.value = true;
    error.value = '';
    certificate.value = null;

    try {
        const { data } = await axios.get(`/api/certificate/validate/${encodeURIComponent(query)}`);
        certificate.value = data;
    } catch (exception) {
        error.value = 'Certificado não encontrado. Confira o código informado.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <section class="certificate-check-page">
        <div class="container py-5">
            <div class="certificate-check-layout">
                <div>
                    <span class="section-kicker">Validação pública</span>
                    <h1>Consultar certificado</h1>
                    <p>Digite o código ou hash de verificação para confirmar a autenticidade de um certificado emitido pela EAD EPI.</p>
                </div>
                <form class="certificate-check-form" @submit.prevent="validateCertificate">
                    <label for="certificate-code">Código ou hash</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="bi bi-patch-check"></i></span>
                        <input id="certificate-code" v-model="code" class="form-control" placeholder="Ex.: CERT-..." autocomplete="off">
                    </div>
                    <button class="btn btn-primary btn-lg w-100" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                        Validar certificado
                    </button>
                    <p v-if="error" class="alert alert-warning mb-0">{{ error }}</p>
                </form>
            </div>

            <article v-if="certificate" class="certificate-result mt-4" :class="{ revoked: !certificate.valid }">
                <div>
                    <span class="section-kicker">{{ statusLabel }}</span>
                    <h2>{{ certificate.course_name }}</h2>
                    <p class="mb-0">Emitido para {{ certificate.student_name }}</p>
                </div>
                <div class="certificate-result-grid">
                    <div><span>Código</span><strong>{{ certificate.code }}</strong></div>
                    <div><span>Carga horária</span><strong>{{ certificate.workload_hours }}h</strong></div>
                    <div><span>Emissão</span><strong>{{ certificate.issued_at ? new Date(certificate.issued_at).toLocaleDateString('pt-BR') : '-' }}</strong></div>
                    <div><span>Situação</span><strong>{{ statusText(certificate.status) }}</strong></div>
                </div>
                <p v-if="certificate.revoked_reason" class="alert alert-danger mb-0">
                    Motivo da revogação: {{ certificate.revoked_reason }}
                </p>
            </article>
        </div>
    </section>
</template>
