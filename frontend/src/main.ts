import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'
// Fontes empacotadas: a CSP só aceita font-src 'self'.
import '@fontsource-variable/manrope'
import '@fontsource-variable/bricolage-grotesque'
import './style.css'
// CSS das bibliotecas como arquivos (servidos de 'self'): a CSP não precisa de style-src 'unsafe-inline'.
import 'sweetalert2/dist/sweetalert2.min.css'
// vue-echarts aplica o próprio CSS via CSSStyleSheet (permitido pela CSP); o arquivo cobre navegadores antigos.
import 'vue-echarts/style.css'
import 'vue3-toastify/dist/index.css'
import App from './App.vue'
import { initTheme } from './presentation/composables/useTheme'
import router from './router'
import { isRenderError, showError } from './core/errors/appError'

// Antes do mount para a tela já abrir no tema certo (a CSP não permite script inline no index.html)
initTheme()

const pinia = createPinia()
const app = createApp(App)

// Erro ao montar uma tela (render/setup) troca a tela pela página de erro 500 em vez de deixá-la em branco.
// Definido antes do Sentry.init: o Sentry encadeia este handler e continua reportando o erro.
app.config.errorHandler = (err, _instance, info) => {
    console.error(err)
    // Falha de API já foi tratada pelo HttpClient (com o código certo: 403, 503...).
    if ((err as any)?.isAxiosError) return
    if (isRenderError(String(info))) showError({ code: 500 })
}

const sentryDsn = import.meta.env.VITE_SENTRY_DSN;
if (sentryDsn) {
    Sentry.init({
        app,
        dsn: sentryDsn,
        environment: import.meta.env.MODE,
        // Eventos vão para a própria API, que repassa ao GlitchTip/Sentry (sem expor o servidor de erros e sem CSP extra).
        tunnel: `${import.meta.env.VITE_API_BASE_URL || ''}/api/monitoring/sentry`,
        integrations: [Sentry.browserTracingIntegration({ router })],
        tracesSampleRate: 0.1,
        sendDefaultPii: false,
    });
}

app.use(pinia)
app.use(router)
app.mount('#app')

// PWA: instalação na tela inicial e notificações push (public/sw.js).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((e) => console.warn('Service worker não registrado', e))
    })
}
