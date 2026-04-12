import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'
import './style.css'
import App from './App.vue'
import router from './router'

import VueApexCharts from 'vue3-apexcharts'

const pinia = createPinia()
const app = createApp(App)

const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
if (sentryDsn) {
    Sentry.init({
        app,
        dsn: sentryDsn,
        environment: import.meta.env.MODE,
        tracesSampleRate: 1.0, 
    });
}

app.use(pinia)
app.use(router)
app.use(VueApexCharts)
app.mount('#app')
