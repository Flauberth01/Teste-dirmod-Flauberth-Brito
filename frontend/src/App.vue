<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const isAuthenticated = computed(() => authStore.isAuthenticated)
const userName = computed(() => authStore.user?.name ?? '')

async function onLogout() {
  await authStore.logout()
  await router.push('/login')
}
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <div class="brand">Gestão de Despesas Internacionais</div>

      <nav class="menu">
        <template v-if="isAuthenticated">
          <RouterLink to="/expenses">Despesas</RouterLink>
          <RouterLink to="/expenses/new">Nova despesa</RouterLink>
          <span class="user">{{ userName }}</span>
          <button type="button" class="link-button" @click="onLogout">Sair</button>
        </template>
        <template v-else>
          <RouterLink to="/login">Entrar</RouterLink>
          <RouterLink to="/register">Cadastrar</RouterLink>
        </template>
      </nav>
    </header>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>
