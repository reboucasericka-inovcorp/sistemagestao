<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useRoute } from 'vue-router'
import AppInput from '@/shared/ui/AppInput.vue'

const route = useRoute()
const isNew = computed(() => route.name === 'contacts.new')

/** Campos descritos em `docs/README.md` (Contactos). */
const form = reactive({
  entity_id: '' as string | number,
  name: '',
  email: '',
  phone: '',
  mobile: '',
  notes: '',
  is_active: true,
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo contacto' : 'Editar contacto' }}</h1>
    <p class="lead">
      Função de contacto é configurável em Configurações; ligação obrigatória à entidade.
    </p>

    <form class="form" @submit.prevent>
      <AppInput id="contact-entity" v-model="form.entity_id" label="ID da entidade" type="number" />
      <AppInput id="contact-name" v-model="form.name" label="Nome" />
      <AppInput id="contact-email" v-model="form.email" label="Email" type="email" />
      <AppInput id="contact-phone" v-model="form.phone" label="Telefone" />
      <AppInput id="contact-mobile" v-model="form.mobile" label="Telemóvel" />
      <AppInput id="contact-notes" v-model="form.notes" label="Observações" />
      <label class="check"><input v-model="form.is_active" type="checkbox" /> Ativo</label>

      <p v-if="!isNew" class="meta">ID na rota: {{ route.params.id }}</p>

      <div class="footer">
        <RouterLink to="/contacts">Cancelar</RouterLink>
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

.check {
  display: block;
  margin: 0.75rem 0 1rem;
  font-size: 0.9rem;
  color: var(--text-h);
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
