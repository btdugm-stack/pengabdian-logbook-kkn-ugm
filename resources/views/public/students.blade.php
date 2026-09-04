@extends('layouts.app')

@section('title', 'Search Mahasiswa')
@section('description', 'Role umum tanpa login: cari data non-sensitif mahasiswa peserta KKN.')

@section('content')
<div class="card">
  <h2>Search Mahasiswa</h2>
  <p>Cari mahasiswa peserta KKN — nomor HP, kontak darurat, dan tanggal lahir tidak ditampilkan untuk publik.</p>

  <form method="get" class="form-row-3">
    <div class="form-group">
      <label>Keyword</label>
      <input name="q" value="{{ $q }}" placeholder="Cari nama, fakultas, program studi...">
    </div>
    <div class="form-group">
      <label>Filter Fakultas</label>
      <select name="faculty">
        <option value="">Semua Fakultas</option>
        @foreach ($faculties as $f)
          <option value="{{ $f }}" {{ $faculty === $f ? 'selected' : '' }}>{{ $f }}</option>
        @endforeach
      </select>
    </div>
    <button class="btn btn-primary">Cari</button>
  </form>
</div>

<div class="grid grid-3" style="margin-top:18px">
  <x-kpi label="Total Mahasiswa" :value="$totalAll" />
  <x-kpi label="Hasil Pencarian" :value="$students->total()" />
  <x-kpi label="Mode" value="View Only" />
</div>

<div class="card" style="margin-top:18px">
  <h2>Hasil Data Mahasiswa</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Fakultas</th><th>Program Studi</th><th>Wilayah Penempatan</th></tr></thead>
      <tbody>
        @forelse ($students as $s)
          <tr>
            <td><strong>{{ $s->name }}</strong></td>
            <td>{{ $s->faculty }}</td>
            <td>{{ $s->study_program }}</td>
            <td>{{ $s->region?->fullPath() }}</td>
          </tr>
        @empty
          <tr><td colspan="4">Data mahasiswa tidak ditemukan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $students->links() }}</div>
</div>
@endsection
