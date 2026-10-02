@extends('layouts.app')

@section('title', 'Dashboard')
@section('description', 'Halo, '.$student->name.'. Ringkasan kegiatan KKN kamu.')

@section('content')
<div class="stack">
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

  @if ($revisions)
    <x-banner tone="danger" title="{{ $revisions }} logbook perlu revisi" action-label="Perbaiki" :action-href="route('logbooks.index')">
      Pembimbing mengembalikan logbook dengan catatan. Perbaiki lalu kirim ulang.
    </x-banner>
  @endif

  @if ($drafts)
    <x-banner tone="warn" title="{{ $drafts }} draft logbook belum dikirim" action-label="Lanjutkan" :action-href="route('logbooks.index')">
      Draft tidak terlihat oleh DPL/kormasit sampai kamu mengirimnya.
    </x-banner>
  @endif

  @if ($pendingApprovals)
    <x-banner tone="warn" title="{{ $pendingApprovals }} presensi bantuan menunggu persetujuanmu" action-label="Tinjau" :action-href="route('assist-attendances.index')">
      Rekan yang membantu program kerjamu menunggu konfirmasi.
    </x-banner>
  @endif
</div>

<div class="grid grid-4" style="margin-top:18px">
  <x-kpi label="Total Logbook" :value="$myLogs" />
  <x-kpi label="Draft" :value="$drafts" tone="{{ $drafts ? 'warn' : null }}" />
  <x-kpi label="Warga Terlibat" :value="$community" />
  <x-kpi label="Lokasi Kegiatan" :value="$locations" />
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Logbook Terbaru</h2>
    <a href="{{ route('logbooks.index') }}" class="table-link">Lihat semua</a>
  </div>
  @if ($recentLogbooks->isEmpty())
    <div class="empty-state">
      <p>Belum ada logbook. Setelah presensi, catat kegiatan pertamamu hari ini.</p>
      <a class="btn btn-primary" href="{{ route('logbooks.create') }}"><x-icon name="plus" :size="16" /> Input Logbook</a>
    </div>
  @else
    <x-logbook-table :logbooks="$recentLogbooks" :show-student="false" :owner-actions="true" />
  @endif
</div>
@endsection
