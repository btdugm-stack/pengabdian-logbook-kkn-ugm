import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import Chart from 'chart.js/auto';

window.L = L;
window.Chart = Chart;

// Peta kegiatan dengan legenda sebagai filter (lihat components/activity-map.blade.php).
// Tiap tombol legenda menyalakan/mematikan satu kategori; warna kategori tetap,
// apa pun yang sedang tampil. Semua teks dari server dipasang lewat textContent
// atau di-escape (audit XSS: nama program/lokasi/mahasiswa adalah input pengguna).
window.renderActivityMap = function ({ markers, groups }) {
  const map = L.map('map').setView([-7.81, 110.36], 11);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

  const esc = (value) => {
    const holder = document.createElement('div');
    holder.textContent = value == null ? '' : String(value);
    return holder.innerHTML;
  };

  const cluster = L.markerClusterGroup();
  const layers = new Map(groups.map((group) => [group.key, []]));

  markers.forEach((m) => {
    const marker = L.circleMarker([m.lat, m.lng], { radius: 10, color: '#fff', weight: 3, fillColor: m.color, fillOpacity: 0.9 });
    marker.bindPopup([
      `<b>${esc(m.title)}</b>`,
      `Tema: ${esc(m.theme)}`,
      m.student ? esc(m.student) : null,
      esc(m.location),
      m.health ? `Kondisi: ${esc(m.health)}` : null,
      `Masyarakat terlibat: ${esc(m.count)}`,
      `Status: ${esc(m.status)}`,
    ].filter(Boolean).join('<br>'));
    layers.get(m.group)?.push(marker);
  });

  map.addLayer(cluster);
  if (markers.length > 0) {
    map.fitBounds(markers.map((m) => [m.lat, m.lng]), { padding: [30, 30], maxZoom: 16 });
  }

  const legend = document.getElementById('map-legend');
  const status = document.getElementById('map-status');
  const active = new Set(groups.map((group) => group.key));
  const buttons = new Map();
  let resetButton = null;

  const draw = () => {
    cluster.clearLayers();
    let shown = 0;
    layers.forEach((groupMarkers, key) => {
      if (active.has(key)) {
        cluster.addLayers(groupMarkers);
        shown += groupMarkers.length;
      }
    });

    buttons.forEach((button, key) => button.setAttribute('aria-pressed', String(active.has(key))));
    if (resetButton) resetButton.hidden = active.size === groups.length;
    if (status) {
      status.textContent = active.size === groups.length
        ? `Menampilkan semua ${markers.length} titik. Klik kategori untuk menyaring.`
        : `Menampilkan ${shown} dari ${markers.length} titik.`;
    }
  };

  if (legend) {
    groups.forEach((group) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'map-legend-item';

      const swatch = document.createElement('span');
      swatch.className = 'map-legend-swatch';
      swatch.style.background = group.color;

      const label = document.createElement('span');
      label.textContent = group.label;

      const count = document.createElement('span');
      count.className = 'map-legend-count';
      count.textContent = layers.get(group.key).length;

      button.append(swatch, label, count);
      button.addEventListener('click', () => {
        // Klik pertama pada legenda penuh = "hanya kategori ini"; setelah itu tiap klik menambah/mengurangi.
        if (active.size === groups.length && groups.length > 1) {
          active.clear();
          active.add(group.key);
        } else if (active.has(group.key)) {
          active.delete(group.key);
        } else {
          active.add(group.key);
        }
        draw();
      });

      buttons.set(group.key, button);
      legend.append(button);
    });

    resetButton = document.createElement('button');
    resetButton.type = 'button';
    resetButton.className = 'map-legend-reset';
    resetButton.textContent = 'Tampilkan semua';
    resetButton.addEventListener('click', () => {
      groups.forEach((group) => active.add(group.key));
      draw();
    });
    legend.append(resetButton);
  }

  draw();
};

// Kolom cari-pilih-tambah (lihat components/search-select.blade.php). Di dalam
// komponen Livewire, nilai yang berlaku selalu dikirim lewat properti "baru"; properti
// dropdown lama dikosongkan supaya pilihan sebelumnya tidak ikut tersimpan saat
// isinya diganti atau dihapus. Pencocokan mengabaikan huruf besar/kecil dan
// spasi ganda - sama dengan FindableByName di server.
document.addEventListener('alpine:init', () => {
  window.Alpine.data('searchSelect', ({ options, value, nameProp, newProp }) => ({
    options,
    query: value,
    open: false,
    active: -1,

    clean(text) {
      return text.trim().replace(/\s+/g, ' ');
    },
    key(text) {
      return this.clean(text).toLowerCase();
    },
    get matches() {
      const q = this.key(this.query);
      const found = q === '' ? this.options : this.options.filter((option) => this.key(option).includes(q));
      return found.slice(0, 50);
    },
    get exact() {
      const q = this.key(this.query);
      return this.options.find((option) => this.key(option) === q) ?? null;
    },
    get canAdd() {
      return this.clean(this.query) !== '' && !this.exact;
    },
    get rows() {
      return this.matches.length + (this.canAdd ? 1 : 0);
    },
    get status() {
      if (this.exact) return 'Memakai data yang sudah ada di daftar.';
      if (this.matches.length > 0) {
        return `Ada ${this.matches.length} data mirip di daftar. Pilih salah satunya bila maksudnya sama, supaya tidak dobel.`;
      }
      return 'Belum ada di daftar. Akan ditambahkan sebagai data baru saat disimpan.';
    },

    // Di form biasa (tanpa Livewire) nilai ikut terkirim lewat atribut name input.
    sync() {
      if (!newProp) return;
      this.$wire[newProp] = this.clean(this.query);
      this.$wire[nameProp] = '';
    },
    onInput() {
      this.open = true;
      this.active = -1;
      this.sync();
    },
    choose(option) {
      this.query = option;
      this.open = false;
      this.active = -1;
      this.sync();
    },
    // Blur, Enter tanpa pilihan, atau baris "Tambah baru": rapikan teksnya, dan
    // bila ternyata sama dengan data yang ada, pakai ejaan yang sudah terdaftar.
    commit() {
      this.query = this.exact ?? this.clean(this.query);
      this.open = false;
      this.active = -1;
      this.sync();
    },
    move(step) {
      this.open = true;
      if (this.rows === 0) return;
      this.active = (this.active + step + this.rows) % this.rows;
      this.$nextTick(() => this.$root.querySelector('.is-active')?.scrollIntoView({ block: 'nearest' }));
    },
    enter() {
      if (this.open && this.active >= 0 && this.active < this.matches.length) {
        this.choose(this.matches[this.active]);
      } else {
        this.commit();
      }
    },
  }));
});

// PWA: daftarkan service worker (lihat public/sw.js) - tanpa ini browser
// tidak akan menawarkan instalasi/aset offline sama sekali.
// URL-nya dari <meta name="sw-url"> (asset('sw.js')) supaya scope ikut subpath
// instalasi, mis. /pengabdian-kkn/sw.js -> scope /pengabdian-kkn/.
const swUrl = document.querySelector('meta[name="sw-url"]')?.content;
if ('serviceWorker' in navigator && swUrl) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register(swUrl).catch(() => {
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
