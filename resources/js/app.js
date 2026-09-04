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
