@extends('layouts.app')

@section('title', 'Data KKN & Biodata')
@section('description', 'Biodata mahasiswa dan konsep input data KKN sebagai dropdown.')

@section('content')
<div class="grid grid-2">
  <div class="card">
    <h2>Biodata Mahasiswa</h2>
    <form method="post" action="{{ route('profile.update') }}">
      @csrf
      @method('patch')
      <div class="form-group">
        <label>Nama</label>
        <input name="name" value="{{ old('name', $student->name) }}" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
      </div>
      <div class="form-row">
        <div class="form-group"><label>Tempat Lahir</label><input name="birth_place" value="{{ old('birth_place', $student->birth_place) }}"></div>
        <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', optional($student->birth_date)->format('Y-m-d')) }}"></div>
      </div>
      <div class="form-group"><label>Asal Fakultas / Sekolah</label><input name="faculty" value="{{ old('faculty', $student->faculty) }}"></div>
      <div class="form-group"><label>Program Studi</label><input name="study_program" value="{{ old('study_program', $student->study_program) }}"></div>
      <div class="form-row">
        <div class="form-group"><label>No HP</label><input name="phone" value="{{ old('phone', $student->phone) }}"></div>
        <div class="form-group"><label>Kontak Darurat</label><input name="emergency_contact" value="{{ old('emergency_contact', $student->emergency_contact) }}"></div>
      </div>
      <button class="btn btn-primary">Simpan Biodata</button>
    </form>
  </div>
  <div class="card">
    <h2>Tema, Program &amp; Lokasi KKN</h2>
    <p>Diisi langsung saat mengisi logbook — pilih dari daftar yang sudah ada, atau ketik yang baru kalau belum tersedia.</p>
    <a class="btn btn-soft" href="{{ route('logbooks.create') }}">Buka Input Logbook</a>
  </div>
</div>
@endsection
