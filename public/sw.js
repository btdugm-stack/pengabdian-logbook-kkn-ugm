// Service worker Logbook KKN - lihat catatan batasan di plan Fase 3:
// ini membuat app shell instalable dan aset statis bisa dibaca offline,
// TAPI TIDAK mengantrekan submit presensi/logbook Livewire saat offline
// (lihat offline-banner di layouts/app.blade.php untuk itu).
const CACHE_NAME = 'logbook-kkn-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Aset build (CSS/JS/font hasil Vite) dan ikon: cache-first, karena nama
  // filenya sudah di-fingerprint per versi sehingga aman disimpan lama.
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request).then((res) => {
        const clone = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        return res;
      }))
    );
    return;
  }

  // Navigasi halaman: network-first, jatuh ke offline.html kalau gagal total.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
  }
});
