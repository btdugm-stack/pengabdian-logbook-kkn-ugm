@extends('layouts.app')

@section('title', 'Cari Mahasiswa')
@section('description', 'Daftar peserta KKN-PPM UGM beserta wilayah penempatannya.')

@section('content')
<div class="card">
  <h2>Cari Mahasiswa</h2>
  <p>Nomor HP, kontak darurat, tanggal lahir, dan kondisi kesehatan tidak ditampilkan untuk publik.</p>

  <form method="get" class="form-row-3">
    <div class="form-group">
      <label for="q">Kata Kunci</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="Nama, fakultas, program studi..." maxlength="100">
    </div>
    <div class="form-group">
      <label for="faculty">Fakultas</label>
      <select id="faculty" name="faculty">
        <option value="">Semua Fakultas</option>
        @foreach ($faculties as $f)
          <option value="{{ $f }}" {{ $faculty === $f ? 'selected' : '' }}>{{ $f }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <button class="btn btn-primary">Cari</button>
    </div>
  </form>
</div>

<div class="grid grid-2" style="margin-top:18px">
  <x-kpi label="Total Peserta" :value="number_format($totalAll, 0, ',', '.')" />
  <x-kpi label="Hasil Pencarian" :value="number_format($students->total(), 0, ',', '.')" />
</div>

<div class="card" style="margin-top:18px">
  <h2>Hasil Pencarian</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Fakultas</th><th>Program Studi</th><th>Wilayah Penempatan</th></tr></thead>
      <tbody>
        @forelse ($students as $s)
          <tr>
            <td><strong>{{ $s->name }}</strong></td>
            <td>{{ $s->faculty ?: '-' }}</td>
            <td>{{ $s->study_program ?: '-' }}</td>
            <td>{{ $s->region?->fullPath() ?? '-' }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-cell">Data mahasiswa tidak ditemukan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $students->links() }}</div>
</div>
@endsection
