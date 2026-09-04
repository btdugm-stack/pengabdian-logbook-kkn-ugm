@extends('layouts.app')

@section('title', 'Dashboard')
@section('description', 'Ringkasan aktivitas KKN kamu.')

@section('content')
@if ($todayAttendance)
  @php
    $sick = in_array($todayAttendance->condition, ['Sakit Ringan', 'Sakit Berat'], true);
    $tone = $sick ? 'danger' : (in_array($todayAttendance->condition, ['Izin', 'Alpha'], true) ? 'warn' : 'success');
  @endphp
  <x-banner :tone="$tone" title="Kamu sudah presensi hari ini" action-label="Ubah" :action-href="route('attendance.check-in')">
    Kondisi {{ $todayAttendance->condition }} &middot; Check-in {{ $todayAttendance->check_in_time->format('H.i') }}
  </x-banner>
@else
  <x-banner tone="warn" title="Kamu belum presensi hari ini" action-label="Presensi Sekarang" :action-href="route('attendance.check-in')">
    Presensi dulu supaya bisa mengisi logbook kegiatan.
  </x-banner>
@endif

<div class="grid grid-4" style="margin-top:18px">
  <x-kpi label="Logbook Saya" :value="$myLogs" />
  <x-kpi label="Masyarakat Terlibat" :value="$community" />
  <x-kpi label="Status Sakit" :value="$mySick" tone="{{ $mySick ? 'danger' : null }}" />
  <x-kpi label="Lokasi" :value="$locations" />
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Logbook Terbaru</h2>
    <a href="{{ route('logbooks.index') }}" class="hint" style="font-weight:700;color:var(--ocean)">Lihat semua</a>
  </div>
  <x-logbook-table :logbooks="$recentLogbooks" />
</div>
@endsection
