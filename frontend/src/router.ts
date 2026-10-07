import { createRouter, createWebHistory } from 'vue-router'
import Login from './views/Login.vue'
import SignUp from './views/SignUp.vue'
import Dashboard from './views/Dashboard.vue'
import Accounts from './views/Accounts.vue'
import Categories from './views/Categories.vue'
import Transactions from './views/Transactions.vue'
import Budgets from './views/Budgets.vue'
import Reports from './views/Reports.vue'
import Calendar from './views/Calendar.vue'
import Settings from './views/Settings.vue'
import Help from './views/Help.vue'
import Workspaces from './views/Workspaces.vue'
import Investments from './views/Investments.vue'
import CreditCards from './views/CreditCards.vue'
import Goals from './views/Goals.vue'
import ForgotPassword from './views/ForgotPassword.vue'
import ErrorPage from './views/ErrorPage.vue'
import { clearError } from './core/errors/appError'

const routes = [
  { path: '/', component: Login },
  { path: '/signup', component: SignUp },
  { path: '/forgot-password', component: ForgotPassword },
  { path: '/dashboard', component: Dashboard },
  { path: '/accounts', component: Accounts },
  { path: '/categories', component: Categories },
  { path: '/transactions', component: Transactions },
  { path: '/budgets', component: Budgets },
  { path: '/reports', component: Reports },
  { path: '/calendar', component: Calendar },
  { path: '/settings', component: Settings },
  { path: '/investments', component: Investments },
  { path: '/credit-cards', component: CreditCards },
  { path: '/goals', component: Goals },
  { path: '/statement', component: () => import('./views/Statement.vue') },
  { path: '/help', component: Help },
  { path: '/workspaces', component: Workspaces },
  { path: '/erro/:code(400|403|500|503)', component: ErrorPage, props: (route: any) => ({ code: Number(route.params.code), reference: route.query.ref }) },
  { path: '/:pathMatch(.*)*', component: ErrorPage, props: { code: 404 } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Mudou de tela: a página de erro da tela anterior some.
router.afterEach(() => {
  clearError()
  sessionStorage.removeItem('chunk-reload')
})

// Depois de um deploy, a aba aberta pode pedir um arquivo de tela que não existe mais: recarrega uma vez.
router.onError((error, to) => {
  const isChunkError = /Failed to fetch dynamically imported module|Importing a module script failed|error loading dynamically imported module/i
    .test(String(error?.message || error))
  if (isChunkError && !sessionStorage.getItem('chunk-reload')) {
    sessionStorage.setItem('chunk-reload', '1')
    window.location.assign(to.fullPath)
    return
  }
  throw error
})

export default router
