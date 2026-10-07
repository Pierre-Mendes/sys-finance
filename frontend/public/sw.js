/* Service worker do PWA: notificações push e tela offline. Não guarda dados financeiros em cache. */
const SHELL_CACHE = 'shell-v1'

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(['/index.html'])))
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== SHELL_CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  )
})

// Só navegação: rede primeiro (sempre a versão nova do app); sem rede, abre o app guardado.
// Chamadas /api nunca passam por cache.
self.addEventListener('fetch', (event) => {
  const { request } = event
  if (request.mode !== 'navigate' || new URL(request.url).pathname.startsWith('/api')) return
  event.respondWith(
    fetch(request)
      .then((response) => {
        const copy = response.clone()
        if (response.ok) caches.open(SHELL_CACHE).then((cache) => cache.put('/index.html', copy))
        return response
      })
      .catch(() => caches.match('/index.html'))
  )
})

self.addEventListener('push', (event) => {
  let data = {}
  try {
    data = event.data ? event.data.json() : {}
  } catch {
    data = { body: event.data ? event.data.text() : '' }
  }
  const url = typeof data.url === 'string' && data.url.startsWith('/') && !data.url.startsWith('//') ? data.url : '/dashboard'
  event.waitUntil(
    self.registration.showNotification(data.title || 'sysfinance', {
      body: data.body || '',
      icon: '/app-icons/icon-192.png',
      badge: '/app-icons/icon-192.png',
      tag: data.tag,
      renotify: Boolean(data.tag),
      data: { url },
    })
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url || '/dashboard'
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      const open = windows.find((w) => new URL(w.url).origin === self.location.origin)
      if (open) {
        open.navigate(url)
        return open.focus()
      }
      return self.clients.openWindow(url)
    })
  )
})
