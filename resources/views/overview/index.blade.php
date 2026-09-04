@extends('layouts.app')

@section('title', 'Overview Wilayah')
@section('description', 'Ringkasan presensi & kondisi mahasiswa, ter-filter sesuai cakupan wilayah akun Anda.')

@section('content')
<div class="card">
  <form method="get" class="form-row-3">
    <div class="form-group">
      <label>Filter Wilayah</label>
      <select name="region_id" onchange="this.form.submit()">
        <option value="">Semua wilayah dalam cakupan saya</option>
        @foreach ($filterOptions as $r)
          <option value="{{ $r->id }}" {{ $selectedRegion?->id === $r->id ? 'selected' : '' }}>{{ $r->fullPath() }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:end">
      <button class="btn btn-primary">Terapkan</button>
    </div>
  </form>
</div>

<div class="grid grid-4" style="margin-top:18px">
  <x-kpi label="Mahasiswa Aktif" :value="$kpi['total']" />
  <x-kpi label="Presensi Hari Ini" :value="$kpi['presentToday'] . '/' . $kpi['total']" />
  <x-kpi label="Kondisi Sakit" :value="$kpi['sickToday']" tone="{{ $kpi['sickToday'] ? 'danger' : null }}" />
  <x-kpi label="Belum Presensi" :value="$kpi['notYetPresent']" tone="{{ $kpi['notYetPresent'] ? 'warn' : null }}" />
</div>

@php
  $sickNames = $students->filter(fn ($s) => in_array($todayAttendances->get($s->id)?->condition, ['Sakit Ringan', 'Sakit Berat']))->pluck('name');
  $absentNames = $students->reject(fn ($s) => $todayAttendances->has($s->id))->pluck('name');
  $attentionNames = $sickNames->concat($absentNames);
@endphp
@if ($attentionNames->isNotEmpty())
  <div style="margin-top:18px">
    <x-banner tone="danger" title="{{ $attentionNames->count() }} mahasiswa perlu perhatian hari ini">
      {{ $attentionNames->take(3)->implode(', ') }}{{ $attentionNames->count() > 3 ? ', dan '.($attentionNames->count() - 3).' lainnya' : '' }}
    </x-banner>
  </div>
@endif

<div class="card" style="margin-top:18px">
  <h2>Tren Kondisi 14 Hari Terakhir</h2>
  <div class="chart-wrap">
    <canvas id="trendChart"></canvas>
  </div>
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Peta Sub-unit</h2>
    <div class="legend">
      <x-pill class="pill-normal">Sehat</x-pill>
      <x-pill class="pill-sick">Sakit</x-pill>
      <x-pill class="pill-warn">Izin/Alpha</x-pill>
    </div>
  </div>
  <div id="map"></div>
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Daftar Mahasiswa</h2>
    <span class="hint" style="margin:0">{{ $students->count() }} mahasiswa</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Wilayah</th><th>Status Hari Ini</th><th>Check-in</th></tr></thead>
      <tbody>
        @forelse ($students as $s)
          @php $att = $todayAttendances->get($s->id) @endphp
          <tr>
            <td><strong>{{ $s->name }}</strong></td>
            <td><small>{{ $s->region?->fullPath() }}</small></td>
            <td class="nowrap">
              @if ($att)
                <x-pill :class="$att->conditionPillClass()">{{ $att->condition }}</x-pill>
              @else
                <x-pill class="pill-warn">Belum presensi</x-pill>
              @endif
            </td>
            <td class="nowrap">{{ $att?->check_in_time?->format('H:i') ?? '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="4">Tidak ada mahasiswa pada wilayah ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
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

  // Audit §XSS: nama mahasiswa/program/lokasi adalah input user -> escape sebelum
  // masuk innerHTML popup supaya tag/atribut HTML dirender sebagai teks literal.
  const esc = (s) => {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  };

  markers.forEach(m => {
    const marker = window.L.circleMarker([m.lat, m.lng], {
      radius: 10, color: '#fff', weight: 3, fillColor: m.color, fillOpacity: 0.9
    });
    marker.bindPopup(
      `<b>${esc(m.title)}</b><br>` +
      `${esc(m.student)}<br>` +
      `${esc(m.location)}<br>` +
      `Kondisi: ${esc(m.health)}<br>` +
      `Status: ${esc(m.status)}`
    );
    cluster.addLayer(marker);
    bounds.push([m.lat, m.lng]);
  });
  map.addLayer(cluster);

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [30, 30] });
  }

  new window.Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
      labels: @json($trend['labels']),
      datasets: [
        { label: 'Sehat', data: @json($trend['sehat']), backgroundColor: '#059669' },
        { label: 'Sakit', data: @json($trend['sakit']), backgroundColor: '#E11D48' },
        { label: 'Izin/Alpha', data: @json($trend['lainnya']), backgroundColor: '#D97706' },
      ],
    },
    options: {
      responsive: true,
      // maintainAspectRatio:false wajib dipasang berbarengan dengan .chart-wrap
      // yang punya height CSS tetap (lihat app.css) - tanpa ini Chart.js
      // menghitung tinggi dari lebar / aspectRatio, jadi di HP yang sempit
      // grafiknya jadi pipih ~170px dan 14 label tanggal saling tumpuk.
      maintainAspectRatio: false,
      scales: {
        x: { stacked: true, ticks: { autoSkip: true, maxRotation: 60, minRotation: 0 } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } },
    },
  });
});
</script>
@endsection
