@extends('layouts.app')

@section('title', 'Search Logbook')
@section('description', 'Role umum tanpa login: cari data logbook (tanpa kondisi kesehatan individu).')

@section('content')
<div class="card">
  <h2>Search Logbook</h2>
  <p>Cari kegiatan KKN berdasarkan mahasiswa, tema, program, atau lokasi — kondisi kesehatan tidak ditampilkan untuk publik.</p>
  <form method="get" class="form-row">
    <div class="form-group">
      <label>Keyword</label>
      <input name="q" value="{{ $q }}" placeholder="Cari mahasiswa, fakultas, tema, program, lokasi, catatan...">
    </div>
    <div class="form-group">
      <label>Mahasiswa</label>
      <select name="student">
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
  <h2>Hasil Search Logbook</h2>
  <x-logbook-table :logbooks="$logbooks" :public-safe="true" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
