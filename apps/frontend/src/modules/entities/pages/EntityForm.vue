<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import AppInput from '@/shared/ui/AppInput.vue'

const route = useRoute()
const isNew = computed(() => route.name === 'entities.new')

/** Campos obrigatórios / modelo descritos em `docs/guide.md` §6 (Entidades). */
const form = reactive({
  number: '',
  nif: '',
  name: '',
  address: '',
  postal_code: '',
  city: '',
  country: '',
  phone: '',
  mobile: '',
  website: '',
  email: '',
  is_client: true,
  is_supplier: false,
  gdpr_consent: false,
  is_active: true,
  notes: '',
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova entidade' : 'Editar entidade' }}</h1>
    <p class="lead">
      Formulário alinhado à especificação (NIF único, morada, código postal <code>XXXX-XXX</code>, RGPD,
      cliente/fornecedor). Persistência via API será ligada nas próximas iterações.
    </p>

    <form class="form" @submit.prevent>
      <AppInput id="entity-number" v-model="form.number" label="Número (incremental)" />
      <AppInput id="entity-nif" v-model="form.nif" label="NIF / VAT ID" />
      <AppInput id="entity-name" v-model="form.name" label="Nome" />
      <AppInput id="entity-address" v-model="form.address" label="Morada" />
      <AppInput id="entity-postal" v-model="form.postal_code" label="Código postal (XXXX-XXX)" />
      <AppInput id="entity-city" v-model="form.city" label="Localidade" />
      <AppInput id="entity-country" v-model="form.country" label="País" />
      <AppInput id="entity-phone" v-model="form.phone" label="Telefone" />
      <AppInput id="entity-mobile" v-model="form.mobile" label="Telemóvel" />
      <AppInput id="entity-website" v-model="form.website" label="Website" />
      <AppInput id="entity-email" v-model="form.email" label="Email" type="email" />

      <fieldset class="checks">
        <label><input v-model="form.is_client" type="checkbox" /> Cliente</label>
        <label><input v-model="form.is_supplier" type="checkbox" /> Fornecedor</label>
        <label><input v-model="form.gdpr_consent" type="checkbox" /> Consentimento RGPD</label>
        <label><input v-model="form.is_active" type="checkbox" /> Ativo</label>
      </fieldset>

      <AppInput id="entity-notes" v-model="form.notes" label="Observações" />

      <p v-if="!isNew" class="meta">ID na rota: {{ route.params.id }}</p>

      <div class="footer">
        <RouterLink to="/entities">Cancelar</RouterLink>
        <button type="button" disabled>Guardar (API em integração)</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.page {
  max-width: 32rem;
}

h1 {
  margin-top: 0;
  text-align: left;
}

.lead {
  text-align: left;
  font-size: 0.9rem;
  color: var(--text);
  margin-bottom: 1.25rem;
}

.form {
  text-align: left;
}

.checks {
  border: none;
  padding: 0;
  margin: 0 0 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.meta {
  font-size: 0.85rem;
  color: var(--text);
}

.footer {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-top: 1rem;
}
</style>
