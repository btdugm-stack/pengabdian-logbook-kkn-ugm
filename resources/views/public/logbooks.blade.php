@extends('layouts.app')

@section('title', 'Cari Logbook')
@section('description', 'Kegiatan KKN yang sudah dikirim mahasiswa, terbuka untuk umum.')

@section('content')
<div class="card">
  <h2>Cari Logbook</h2>
  <p>Cari kegiatan berdasarkan mahasiswa, tema, program, atau lokasi. Kondisi kesehatan dan catatan pribadi tidak ditampilkan untuk publik.</p>
  <form method="get" class="form-row">
    <div class="form-group">
      <label for="q">Kata Kunci</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="Nama, fakultas, tema, program, lokasi, catatan..." maxlength="100">
    </div>
    <div class="form-group">
      <label for="student">Mahasiswa</label>
      <select id="student" name="student">
        <option value="">Semua Mahasiswa</option>
        @foreach ($students as $s)
          <option value="{{ $s->id }}" {{ (string) $studentId === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group" style="display:flex;align-items:end">
      <button class="btn btn-primary">Cari Logbook</button>
    </div>
  </form>
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Hasil Pencarian</h2>
    <span class="hint" style="margin:0">{{ $logbooks->total() }} logbook</span>
  </div>
  <x-logbook-table :logbooks="$logbooks" :public-safe="true" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
