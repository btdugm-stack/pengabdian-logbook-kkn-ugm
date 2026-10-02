@extends('layouts.app')

@section('title', $title)
@section('description', $public ? 'Sebaran lokasi kegiatan KKN dari logbook yang sudah dikirim.' : 'Lokasi kegiatan dari logbook kamu.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>{{ count($map['markers']) }} titik kegiatan</h2>
    @if ($public)
      <span class="hint" style="margin:0">Kondisi kesehatan tidak ditampilkan untuk publik.</span>
    @endif
  </div>
  <x-activity-map :map="$map" empty-text="Belum ada logbook dengan koordinat lokasi. Isi kolom koordinat saat input logbook agar kegiatan muncul di peta." />
</div>
@endsection
