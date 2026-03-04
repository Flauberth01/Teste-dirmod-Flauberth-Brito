import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import ExpenseCreateView from '../views/ExpenseCreateView.vue'
import ExpenseDetailView from '../views/ExpenseDetailView.vue'
import ExpensesListView from '../views/ExpensesListView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { public: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { public: true },
  },
  {
    path: '/expenses',
    name: 'expenses-list',
    component: ExpensesListView,
    meta: { requiresAuth: true },
  },
  {
    path: '/expenses/new',
    name: 'expenses-new',
    component: ExpenseCreateView,
    meta: { requiresAuth: true },
  },
  {
    path: '/expenses/:id',
    name: 'expenses-detail',
    component: ExpenseDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/expenses',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth) {
    if (authStore.isAuthenticated) {
      return true
    }

    const hasSession = await authStore.fetchMe({ allowWithoutToken: true })

    if (!hasSession) {
      return {
        path: '/login',
        query: {
          redirect: to.fullPath,
        },
      }
    }
  }

  if ((to.path === '/login' || to.path === '/register') && authStore.isAuthenticated) {
    return { path: '/expenses' }
  }

  return true
})

export default router
