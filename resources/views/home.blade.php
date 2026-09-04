@extends('layouts.app')

@section('title', 'Beranda')
@section('description', 'Laravel + MySQL - presensi harian, presensi bantuan, dan overview per-wilayah.')

@section('content')
<section class="hero">
  <div>
    <h1>Pengabdian: Logbook KKN</h1>
    <p>Mahasiswa login dengan Google SSO untuk input logbook, sedangkan role umum tanpa login dapat melakukan search mahasiswa, search logbook, dan melihat peta.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="{{ route('login') }}">Login Mahasiswa via SSO Google</a>
      <a class="btn btn-soft" href="{{ route('search.students') }}">Search Mahasiswa</a>
      <a class="btn btn-outline" href="{{ route('search.logbooks') }}">Search Logbook</a>
    </div>
  </div>
  <div class="grid grid-2">
    <div class="flow-box"><strong>1. Login SSO</strong><br><span>Mahasiswa masuk dengan Google SSO.</span></div>
    <div class="flow-box"><strong>2. Input Data</strong><br><span>Data baru masuk DB dan menjadi dropdown.</span></div>
    <div class="flow-box"><strong>3. Logbook</strong><br><span>Progress, kesehatan, lokasi, personal info.</span></div>
    <div class="flow-box"><strong>4. Role Umum</strong><br><span>Search mahasiswa, logbook, dan view peta.</span></div>
  </div>
</section>

<div class="grid grid-4" style="margin-top:20px">
  <x-kpi label="Mahasiswa" :value="$totalStudents" />
  <x-kpi label="Logbook" :value="$totalLogs" />
  <x-kpi label="Status Sakit" :value="$sick" tone="{{ $sick ? 'danger' : null }}" />
  <x-kpi label="Lokasi" :value="$totalLocations" />
</div>
@endsection
