import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import Chart from 'chart.js/auto';

window.L = L;
window.Chart = Chart;

// PWA: daftarkan service worker (lihat public/sw.js) - tanpa ini browser
// tidak akan menawarkan instalasi/aset offline sama sekali.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {
      // Diamkan - PWA adalah peningkatan progresif, kegagalan register
      // tidak boleh mengganggu pemakaian aplikasi biasa.
    });
  });
}

// PWA: tombol "Instal Aplikasi" di footer sidebar (lihat layouts/app.blade.php).
// Browser hanya menembak `beforeinstallprompt` kalau kriteria installable
// terpenuhi (manifest valid, sw terdaftar, dkk) - makanya tombolnya default
// hidden dan baru dimunculkan begitu event ini benar-benar datang. iOS Safari
// tidak pernah menembak event ini sama sekali (instal lewat menu Share bawaan),
// jadi di iOS tombol ini akan tetap tersembunyi - itu memang batasan platform,
// bukan bug.
let deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  deferredInstallPrompt = event;
  document.getElementById('pwa-install-btn')?.removeAttribute('hidden');
});
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('pwa-install-btn')?.addEventListener('click', async (e) => {
    if (!deferredInstallPrompt) return;
    e.currentTarget.setAttribute('hidden', '');
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
  });
});
window.addEventListener('appinstalled', () => {
  deferredInstallPrompt = null;
  document.getElementById('pwa-install-btn')?.setAttribute('hidden', '');
});

// Indikator status koneksi untuk halaman presensi/logbook - lihat batasan
// PWA di plan Fase 3 (bukan antrian offline penuh untuk submit Livewire).
function updateOnlineBanner() {
  const banner = document.getElementById('offline-banner');
  if (!banner) return;
  banner.hidden = navigator.onLine;
}
window.addEventListener('online', updateOnlineBanner);
window.addEventListener('offline', updateOnlineBanner);
document.addEventListener('DOMContentLoaded', updateOnlineBanner);

/*
 * Sidebar collapse/expand.
 * - Desktop: class `sidebar-collapsed` di <html> -> rail 76px (ikon saja),
 *   disimpan di localStorage. Class-nya sudah dipasang lebih dulu oleh
 *   script inline kecil di <head> supaya tidak ada kedip saat load.
 * - <=980px: sidebar jadi drawer, dibuka lewat class `drawer-open`.
 */
const SIDEBAR_KEY = 'logbook-kkn:sidebar-collapsed';

function isMobileLayout() {
  return window.matchMedia('(max-width: 980px)').matches;
}

function closeDrawer() {
  document.documentElement.classList.remove('drawer-open');
}

document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;

  document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    const collapsed = root.classList.toggle('sidebar-collapsed');
    try {
      localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
    } catch {
      // Mode privat/site-data diblokir: toggle tetap jalan untuk sesi ini.
    }
  });

  document.getElementById('drawer-toggle')?.addEventListener('click', () => {
    root.classList.toggle('drawer-open');
  });

  document.getElementById('drawer-overlay')?.addEventListener('click', closeDrawer);

  // Pindah halaman lewat menu di drawer -> tutup dulu biar tidak menutupi konten.
  document.querySelectorAll('.sidebar .menu a').forEach((link) => {
    link.addEventListener('click', () => isMobileLayout() && closeDrawer());
  });

  document.addEventListener('keydown', (e) => e.key === 'Escape' && closeDrawer());
  window.addEventListener('resize', () => !isMobileLayout() && closeDrawer());
});
