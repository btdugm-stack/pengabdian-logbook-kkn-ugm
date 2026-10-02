@props(['target' => 'coordinate'])
{{-- Tombol "Lokasi saat ini" untuk form Livewire. Sebelumnya kegagalan
     geolokasi (izin ditolak, GPS mati, browser tanpa dukungan) diam saja -
     pengguna tidak tahu kenapa koordinat tidak terisi. --}}
<div class="geo-field" x-data="{ locating: false, geoError: '' }">
  <button type="button" class="btn btn-outline" x-bind:disabled="locating"
    x-on:click="
      geoError = '';
      if (! navigator.geolocation) {
        geoError = 'Browser ini tidak mendukung deteksi lokasi. Isi koordinat secara manual.';
        return;
      }
      locating = true;
      navigator.geolocation.getCurrentPosition(
        (p) => { locating = false; $wire.set('{{ $target }}', p.coords.latitude.toFixed(7) + ', ' + p.coords.longitude.toFixed(7)); },
        (e) => { locating = false; geoError = e.code === 1 ? 'Izin lokasi ditolak. Aktifkan izin lokasi di pengaturan browser, atau isi koordinat manual.' : 'Lokasi belum bisa dideteksi. Pastikan GPS aktif lalu coba lagi.'; },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
      );
    ">
    <x-icon name="locate" :size="16" />
    <span x-text="locating ? 'Mendeteksi…' : 'Lokasi saat ini'">Lokasi saat ini</span>
  </button>
  <div class="field-error" x-show="geoError" x-text="geoError" x-cloak></div>
</div>
