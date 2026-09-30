import { ref, computed } from 'vue'
import api from '@/data/api/HttpClient'

export type PushState = 'unsupported' | 'needs-install' | 'denied' | 'off' | 'on'

/** A chave VAPID vem em base64url; o navegador espera os bytes. */
export function urlBase64ToUint8Array(base64: string): Uint8Array<ArrayBuffer> {
  const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(padded)
  const bytes = new Uint8Array(new ArrayBuffer(raw.length))
  for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i)
  return bytes
}

export function isIos(ua = navigator.userAgent): boolean {
  return /iphone|ipad|ipod/i.test(ua)
}

export function isStandalone(): boolean {
  return window.matchMedia?.('(display-mode: standalone)').matches || (navigator as any).standalone === true
}

/** Liga/desliga as notificações push deste dispositivo. */
export function usePushNotifications() {
  const state = ref<PushState>('off')
  const busy = ref(false)

  const supported = computed(() => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window)

  const registration = () => navigator.serviceWorker.ready

  const refresh = async () => {
    if (!supported.value) {
      // No iPhone o push só existe com o app instalado na tela inicial (iOS 16.4+).
      state.value = isIos() && !isStandalone() ? 'needs-install' : 'unsupported'
      return
    }
    if (Notification.permission === 'denied') {
      state.value = 'denied'
      return
    }
    const sub = await (await registration()).pushManager.getSubscription()
    state.value = sub ? 'on' : 'off'
  }

  const enable = async () => {
    busy.value = true
    try {
      const permission = await Notification.requestPermission()
      if (permission !== 'granted') {
        state.value = permission === 'denied' ? 'denied' : 'off'
        return false
      }
      const { data } = await api.get('/api/reminders/push/public-key')
      const reg = await registration()
      const sub = (await reg.pushManager.getSubscription())
        ?? (await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(data.publicKey) }))
      await api.post('/api/reminders/push/subscriptions', sub.toJSON())
      state.value = 'on'
      return true
    } finally {
      busy.value = false
    }
  }

  const disable = async () => {
    busy.value = true
    try {
      const sub = await (await registration()).pushManager.getSubscription()
      if (sub) {
        await api.delete('/api/reminders/push/subscriptions', { data: { endpoint: sub.endpoint } })
        await sub.unsubscribe()
      }
      state.value = 'off'
    } finally {
      busy.value = false
    }
  }

  return { state, busy, supported, refresh, enable, disable }
}
