@extends('layouts.app')

@section('title', 'Beranda')
@section('description', 'Ringkasan kegiatan KKN-PPM UGM yang terbuka untuk umum.')

@section('content')
<section class="hero">
  <div>
    <h1>Logbook KKN-PPM UGM</h1>
    <p>Presensi harian, catatan kegiatan, dan sebaran lokasi KKN dalam satu tempat. Data di bawah terbuka untuk umum; presensi dan input logbook membutuhkan akun UGM.</p>
    <div class="hero-actions">
      <a class="btn btn-soft" href="{{ route('search.students') }}">Cari Mahasiswa</a>
      <a class="btn btn-outline" href="{{ route('search.logbooks') }}">Cari Logbook</a>
      <a class="btn btn-outline" href="{{ route('map.public') }}">Peta Sebaran</a>
    </div>
  </div>
  <div class="grid grid-2">
    <div class="flow-box"><strong>Presensi harian</strong><br><span>Kehadiran dan kondisi kesehatan dicatat sekali sehari.</span></div>
    <div class="flow-box"><strong>Logbook kegiatan</strong><br><span>Tema, program kerja, lokasi, dan warga yang terlibat.</span></div>
    <div class="flow-box"><strong>Presensi bantuan</strong><br><span>Kontribusi lintas sub-unit dengan persetujuan pemilik program.</span></div>
    <div class="flow-box"><strong>Pantauan wilayah</strong><br><span>Kormasit dan DPL memantau sub-unitnya masing-masing.</span></div>
  </div>
</section>

<div class="grid grid-4" style="margin-top:18px">
  <x-kpi label="Mahasiswa" :value="$totalStudents" />
  <x-kpi label="Logbook" :value="$totalLogs" />
  <x-kpi label="Status Sakit" :value="$sick" tone="{{ $sick ? 'danger' : null }}" />
  <x-kpi label="Lokasi" :value="$totalLocations" />
</div>
@endsection
