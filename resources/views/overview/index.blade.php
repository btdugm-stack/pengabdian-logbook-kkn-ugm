@extends('layouts.app')

@section('title', 'Overview Wilayah')
@section('description', 'Ringkasan presensi dan kondisi mahasiswa dalam cakupan akun Anda.')

@section('content')
@if ($unassigned)
  <div style="margin-bottom:18px">
    @if (auth()->user()->hasAnyRole(\App\Models\Student::ASSIGNED_SCOPE_ROLES))
      <x-banner tone="warn" title="Belum ada mahasiswa bimbingan" action-label="Pilih Mahasiswa" :action-href="route('advisees.edit')">
        Karena itu belum ada mahasiswa yang tampil. Pilih sendiri mahasiswa bimbingan Anda, atau minta admin LPPM menugaskannya.
      </x-banner>
    @elseif (auth()->user()->hasAnyRole(\App\Models\Student::UNIT_ROLES))
      <x-banner tone="warn" title="Fakultas akun Anda belum diisi">
        Karena itu belum ada mahasiswa yang tampil. Minta admin LPPM mengisi fakultas akun Anda.
      </x-banner>
    @else
      <x-banner tone="warn" title="Akun Anda belum ditugaskan ke wilayah mana pun">
        Karena itu belum ada mahasiswa yang tampil. Minta admin LPPM mengisi wilayah akun Anda.
      </x-banner>
    @endif
  </div>
@endif

@if ($students->isEmpty() && ! $selectedRegion && auth()->user()->hasAnyRole(\App\Models\Student::ACCOUNT_MANAGER_ROLES))
  <div style="margin-bottom:18px">
    <x-banner tone="warn" title="Belum ada mahasiswa terdaftar" action-label="Kelola Peserta" :action-href="route('admin.participants.index')">
      Daftarkan akun mahasiswa dan pembimbing supaya mereka bisa masuk dengan akun Google UGM.
    </x-banner>
  </div>
@endif

<div class="card">
  <form method="get" class="form-row-3">
    <div class="form-group">
      <label for="region_id">Filter Wilayah</label>
      <select id="region_id" name="region_id" onchange="this.form.submit()">
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
      @if ($aggregateOnly)
        {{ $sickNames->count() }} sakit, {{ $absentNames->count() }} belum presensi.
      @else
        {{ $attentionNames->take(3)->implode(', ') }}{{ $attentionNames->count() > 3 ? ', dan '.($attentionNames->count() - 3).' lainnya' : '' }}
      @endif
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
    <h2>{{ $aggregateOnly ? 'Peta Sebaran Kegiatan' : 'Peta Kegiatan' }}</h2>
    <span class="hint" style="margin:0">{{ count($map['markers']) }} titik</span>
  </div>
  <x-activity-map :map="$map" empty-text="Belum ada logbook berkoordinat dari mahasiswa dalam cakupan ini." />
</div>

@unless ($aggregateOnly)
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
            <td><a class="table-link" href="{{ route('overview.student', $s) }}">{{ $s->name }}</a></td>
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
          <tr><td colspan="4" class="empty-cell">Tidak ada mahasiswa pada wilayah ini.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endunless
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
