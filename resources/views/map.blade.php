@extends('layouts.app')

@section('title', $title)
@section('description', 'Peta lokasi berdasarkan logbook mahasiswa.')

@section('content')
<div class="grid grid-2">
  <div class="card">
    <h2>Peta Lokasi Logbook</h2>
    <p>Marker diambil dari lokasi yang diinput pada logbook.</p>
  </div>
  <div class="card">
    <h2>Legenda</h2>
    <p>
      @if ($public)
        <x-pill class="pill-normal">Marker netral — kondisi kesehatan tidak ditampilkan untuk publik</x-pill>
      @else
        <x-pill class="pill-normal">Sehat</x-pill>
        <x-pill class="pill-sick">Sakit Ringan/Berat</x-pill>
        <x-pill class="pill-warn">Izin/Alpha</x-pill>
      @endif
    </p>
  </div>
</div>
<div class="card" style="margin-top:18px;padding:12px">
  <div id="map"></div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const markers = @json($markers);
  const map = window.L.map('map').setView([-7.81, 110.36], 11);
  window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  const cluster = window.L.markerClusterGroup();
  const bounds = [];

  // Audit §XSS: nilai dari DB (nama mahasiswa/program/lokasi = input user) TIDAK
  // boleh masuk popup sebagai HTML mentah. esc() mengubah teks menjadi string
  // yang sudah di-escape, jadi <img onerror=...> dirender sebagai teks literal.
  const esc = (s) => {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  };

  markers.forEach(m => {
    const marker = window.L.circleMarker([m.lat, m.lng], {
      radius: 10,
      color: '#fff',
      weight: 3,
      fillColor: m.color,
      fillOpacity: 0.9
    });
    marker.bindPopup(
      `<b>${esc(m.title)}</b><br>` +
      `${esc(m.student)}<br>` +
      `${esc(m.location)}<br>` +
      (m.health ? `Kondisi: ${esc(m.health)}<br>` : '') +
      `Masyarakat terlibat: ${esc(m.count)}<br>` +
      `Status: ${esc(m.status)}`
    );
    cluster.addLayer(marker);
    bounds.push([m.lat, m.lng]);
  });
  map.addLayer(cluster);

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [30, 30] });
  }
});
</script>
@endsection
