@extends('layouts.app')

@php($isMahasiswa = $student->isParticipant())

@section('title', $isMahasiswa ? 'Data KKN & Biodata' : 'Profil')
@section('description', $isMahasiswa ? 'Lengkapi biodata dan kontak darurat - dipakai DPL/kormasit bila terjadi kondisi darurat.' : 'Data akun dan cakupan wilayah Anda.')

@section('content')
<div class="grid grid-2">
  <div class="card">
    <h2>{{ $isMahasiswa ? 'Biodata Mahasiswa' : 'Data Akun' }}</h2>
    <form method="post" action="{{ route('profile.update') }}">
      @csrf
      @method('patch')
      <div class="form-group">
        <label for="email">Email Login</label>
        <input id="email" value="{{ $student->email }}" disabled>
        <div class="hint">Email tidak bisa diubah sendiri. Hubungi admin LPPM bila keliru.</div>
      </div>
      <div class="form-group">
        <label for="name">Nama</label>
        <input id="name" name="name" value="{{ old('name', $student->name) }}" maxlength="150" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
      </div>
      @if ($isMahasiswa)
        <div class="form-row">
          <div class="form-group">
            <label for="birth_place">Tempat Lahir</label>
            <input id="birth_place" name="birth_place" value="{{ old('birth_place', $student->birth_place) }}" maxlength="100">
            @error('birth_place') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="form-group">
            <label for="birth_date">Tanggal Lahir</label>
            <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', optional($student->birth_date)->format('Y-m-d')) }}" max="{{ today()->subDay()->toDateString() }}">
            @error('birth_date') <div class="field-error">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="form-group">
          <label for="faculty">Fakultas / Sekolah</label>
          <x-search-select id="faculty" name="faculty" noun="fakultas" :options="$faculties" :value="old('faculty', $student->faculty)" :invalid="$errors->has('faculty')" />
          @error('faculty') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="study_program">Program Studi</label>
          <x-search-select id="study_program" name="study_program" noun="program studi" :options="$studyPrograms" :value="old('study_program', $student->study_program)" :invalid="$errors->has('study_program')" />
          @error('study_program') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="kkn_period">Periode KKN</label>
          <x-search-select id="kkn_period" name="kkn_period" noun="periode" :options="$periods" placeholder="Mis. Periode 2 Tahun 2026" :value="old('kkn_period', $student->kkn_period)" :invalid="$errors->has('kkn_period')" />
          @error('kkn_period') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
          <label for="kkn_theme">Tema KKN</label>
          <x-search-select id="kkn_theme" name="kkn_theme" noun="tema" :options="$themes" :value="old('kkn_theme', $student->kkn_theme)" :invalid="$errors->has('kkn_theme')" />
          <div class="hint">Periode dan tema ini otomatis terisi di form Input Logbook.</div>
          @error('kkn_theme') <div class="field-error">{{ $message }}</div> @enderror
        </div>
      @endif
      <div class="form-row">
        <div class="form-group">
          <label for="phone">No HP</label>
          <input id="phone" type="tel" name="phone" value="{{ old('phone', $student->phone) }}" placeholder="08xxxxxxxxxx" maxlength="50">
          @error('phone') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        @if ($isMahasiswa)
          <div class="form-group">
            <label for="emergency_contact">Kontak Darurat</label>
            <input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $student->emergency_contact) }}" placeholder="Ibu - 08xxxxxxxxxx" maxlength="100">
            @error('emergency_contact') <div class="field-error">{{ $message }}</div> @enderror
          </div>
        @endif
      </div>
      <button class="btn btn-primary">Simpan</button>
    </form>
  </div>

  <div class="card">
    @if ($isMahasiswa)
      <h2>Penempatan KKN</h2>
      <dl class="detail-list">
        <div><dt>Wilayah</dt><dd>{{ $student->region?->fullPath() ?? 'Belum ditentukan' }}</dd></div>
        <div><dt>DPL Pembimbing</dt><dd>{{ $student->advisors->pluck('name')->implode(', ') ?: 'Belum ditugaskan' }}</dd></div>
      </dl>
      <p style="margin-top:16px">Tema, program kerja, dan lokasi kegiatan diisi langsung saat mengisi logbook &mdash; pilih dari daftar yang sudah ada, atau ketik baru bila belum tersedia.</p>
      <a class="btn btn-soft" href="{{ route('logbooks.create') }}">Buka Input Logbook</a>
    @else
      <h2>Peran &amp; Cakupan</h2>
      <dl class="detail-list">
        <div><dt>Peran</dt><dd>{{ $student->roleLabel() }}</dd></div>
        <div>
          <dt>Cakupan Wilayah</dt>
          <dd>
            @if ($student->hasAnyRole(\App\Models\Student::UNIT_ROLES))
              Unit {{ $student->faculty ?: 'belum diisi - hubungi admin LPPM' }}
            @elseif ($student->hasAnyRole(\App\Models\Student::FULL_ACCESS_ROLES))
              Seluruh wilayah
            @else
              {{ $student->region?->fullPath() ?? 'Belum ditugaskan - hubungi admin LPPM' }}
            @endif
          </dd>
        </div>
      </dl>
    @endif
  </div>
</div>
@endsection
