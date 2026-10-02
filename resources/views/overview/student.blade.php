@extends('layouts.app')

@section('title', $student->name)
@section('description', $student->region?->fullPath() ?? 'Wilayah penempatan belum ditentukan')

@section('content')
<a href="{{ route('overview') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Overview</a>

@if ($todayAttendance)
  @php($isSick = in_array($todayAttendance->condition, ['Sakit Ringan', 'Sakit Berat'], true))
  <x-banner :tone="$isSick ? 'danger' : (in_array($todayAttendance->condition, ['Izin', 'Alpha'], true) ? 'warn' : 'success')"
    title="Hari ini: {{ $todayAttendance->condition }} · check-in {{ $todayAttendance->check_in_time?->format('H.i') ?? '-' }}">
    {{ $todayAttendance->condition_note ?: 'Tidak ada catatan kondisi.' }}
  </x-banner>
@else
  <x-banner tone="warn" title="Belum presensi hari ini">
    Hubungi mahasiswa bila hingga siang belum ada kabar.
  </x-banner>
@endif

<div class="grid grid-2" style="margin-top:18px">
  <div class="card">
    <h2>Biodata &amp; Kontak</h2>
    <dl class="detail-list">
      <div><dt>Email</dt><dd><a href="mailto:{{ $student->email }}">{{ $student->email }}</a></dd></div>
      <div><dt>Fakultas</dt><dd>{{ $student->faculty ?: '-' }}</dd></div>
      <div><dt>Program Studi</dt><dd>{{ $student->study_program ?: '-' }}</dd></div>
      <div><dt>Wilayah</dt><dd>{{ $student->region?->fullPath() ?? '-' }}</dd></div>
      <div>
        <dt>No HP</dt>
        <dd>
          @if ($student->phone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $student->phone) }}">{{ $student->phone }}</a>
          @else
            <span class="muted">Belum diisi mahasiswa</span>
          @endif
        </dd>
      </div>
      <div><dt>Kontak Darurat</dt><dd>{{ $student->emergency_contact ?: '-' }}</dd></div>
      <div><dt>DPL Pembimbing</dt><dd>{{ $student->advisors->pluck('name')->implode(', ') ?: 'Belum ditugaskan' }}</dd></div>
    </dl>
  </div>

  <div class="card">
    <h2>Riwayat Presensi 30 Hari</h2>
    <div class="table-wrap scroll-y">
      <table>
        <thead><tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Kondisi</th></tr></thead>
        <tbody>
          @forelse ($attendances as $a)
            <tr>
              <td class="nowrap">{{ $a->attendance_date->translatedFormat('d M Y') }}</td>
              <td>{{ $a->check_in_time?->format('H:i') ?? '-' }}</td>
              <td>{{ $a->check_out_time?->format('H:i') ?? '-' }}</td>
              <td>
                <x-pill :class="$a->conditionPillClass()">{{ $a->condition }}</x-pill>
                @if ($a->condition_note)
                  <br><small class="muted">{{ $a->condition_note }}</small>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty-cell">Belum ada riwayat presensi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Logbook</h2>
    <div class="head-actions">
      <span class="hint" style="margin:0">{{ $logbooks->total() }} logbook</span>
      @if ($canCorrect)
        <a class="btn btn-outline" href="{{ route('admin.logbooks.create', $student) }}"><x-icon name="plus" :size="16" /> Input atas nama</a>
      @endif
    </div>
  </div>
  <x-logbook-table :logbooks="$logbooks" :show-student="false" :reviewable="$canReview" :admin-actions="$canCorrect" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
