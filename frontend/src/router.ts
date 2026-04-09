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

const routes = [
  { path: '/', component: Login },
  { path: '/signup', component: SignUp },
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
  { path: '/help', component: Help },
  { path: '/workspaces', component: Workspaces }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
