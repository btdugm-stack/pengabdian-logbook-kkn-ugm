@extends('layouts.app')

@section('title', 'Tambah Akun')
@section('description', 'Daftarkan satu akun mahasiswa, pembimbing, atau admin.')

@section('content')
<a href="{{ route('admin.participants.index') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Daftar Akun</a>

<div class="grid grid-2">
  <div class="card">
    <h2>Data Akun</h2>
    <form method="post" action="{{ route('admin.participants.store') }}">
      @csrf
      @include('admin.participants.form', ['student' => null])
      <div class="hero-actions">
        <button class="btn btn-primary" type="submit" name="after" value="index">Simpan</button>
        <button class="btn btn-outline" type="submit" name="after" value="create">Simpan &amp; Tambah Lagi</button>
      </div>
    </form>
  </div>

  <div>
    @include('admin.participants.role-guide')
    <div class="card" style="margin-top:16px">
      <h2>Banyak akun sekaligus?</h2>
      <p>Gunakan impor CSV untuk mendaftarkan satu angkatan atau satu wilayah sekaligus.</p>
      <a class="btn btn-soft" href="{{ route('admin.participants.import') }}"><x-icon name="upload" :size="16" /> Impor CSV</a>
    </div>
  </div>
</div>
@endsection
