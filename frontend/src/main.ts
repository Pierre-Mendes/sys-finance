import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'
// Fontes empacotadas: a CSP só aceita font-src 'self'.
import '@fontsource-variable/manrope'
import '@fontsource-variable/bricolage-grotesque'
import './style.css'
// CSS das bibliotecas como arquivos (servidos de 'self'): a CSP não precisa de style-src 'unsafe-inline'.
import 'sweetalert2/dist/sweetalert2.min.css'
import 'apexcharts/dist/apexcharts.css'
import 'apexcharts/dist/apexcharts-legend.css'
import 'vue3-toastify/dist/index.css'
import App from './App.vue'
import router from './router'

import VueApexCharts from 'vue3-apexcharts'

// ApexCharts lê opções globais de window.Apex: sem isso ele injeta <style id="apexcharts-css"> e o CSS da legenda.
;(window as any).Apex = { ...((window as any).Apex || {}), chart: { ...((window as any).Apex?.chart || {}), injectStyleSheet: false } }

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

// PWA: instalação na tela inicial e notificações push (public/sw.js).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((e) => console.warn('Service worker não registrado', e))
    })
}
