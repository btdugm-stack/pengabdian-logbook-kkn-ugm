@extends('layouts.auth')

@section('title', 'Daftar - Logbook KKN UGM')

@section('content')
<h3>Daftar akun</h3>

@if (! $identity)
  <p class="sub">Pendaftaran memakai akun Google UGM Anda, tanpa password baru. Setelah formulir dikirim, admin akan memeriksa dan menyetujuinya.</p>

  <a href="{{ route('auth.google') }}" class="btn btn-google btn-block">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8Z"/><path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3c-1.1.7-2.5 1.2-4.1 1.2-3.1 0-5.8-2.1-6.7-5H1.3v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.3 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8l4-3.1Z"/><path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.3 6.6l4 3.1c.9-2.9 3.6-5 6.7-5Z"/></svg>
    Lanjutkan dengan Akun UGM
  </a>

  <p class="auth-note">Mahasiswa memakai email <strong>@mail.ugm.ac.id</strong>, dosen &amp; pembimbing <strong>@ugm.ac.id</strong>. Bila akun Anda sudah terdaftar, Anda langsung masuk.</p>
@else
  <p class="sub">Lengkapi data berikut; semuanya wajib kecuali catatan. Admin akan memeriksa dan menetapkan peran serta wilayah Anda sebelum akun aktif.</p>

  @if ($previous?->status === \App\Models\RegistrationRequest::STATUS_REJECTED)
    <div class="alert alert-error" role="alert">
      Pendaftaran sebelumnya ditolak{{ $previous->rejection_reason ? ': '.$previous->rejection_reason : '.' }} Anda bisa memperbaiki data dan mengirim ulang.
    </div>
  @endif

  <form method="post" action="{{ route('register.store') }}">
    @csrf

    <div class="form-group">
      <label for="email">Email Google UGM</label>
      <input id="email" value="{{ $identity['email'] }}" disabled>
      <div class="hint">Diambil dari akun Google yang baru saja Anda pakai.</div>
    </div>

    <div class="form-group">
      <label for="nama">Nama Lengkap</label>
      <input id="nama" name="nama" value="{{ old('nama', $previous?->name ?? $identity['name']) }}" maxlength="150" required>
      @error('nama') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="peran">Mendaftar sebagai</label>
      <select id="peran" name="peran" required>
        <option value="">Pilih peran...</option>
        @foreach (\Illuminate\Support\Arr::only(\App\Models\Student::ROLE_LABELS, \App\Models\Student::SELF_REGISTRABLE_ROLES) as $value => $label)
          <option value="{{ $value }}" @selected(old('peran', $previous?->requested_role) === $value)>{{ $label }}</option>
        @endforeach
      </select>
      @error('peran') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="wilayah">Wilayah</label>
      <input id="wilayah" name="wilayah" list="region-paths" value="{{ old('wilayah', $previous?->region_path) }}" placeholder="Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A" maxlength="600" required>
      <datalist id="region-paths">
        @foreach ($regionPaths as $path)
          <option value="{{ $path }}"></option>
        @endforeach
      </datalist>
      <div class="hint">Lokasi penempatan, atau wilayah yang Anda bimbing bila mendaftar sebagai DPL. Pilih dari saran atau ketik; pisahkan tingkat dengan <code class="inline">/</code>.</div>
      @error('wilayah') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="fakultas">Fakultas / Sekolah</label>
      <x-search-select id="fakultas" name="fakultas" :required="true" noun="fakultas" :options="$faculties"
        :value="old('fakultas', $previous?->faculty)" :invalid="$errors->has('fakultas')" />
      @error('fakultas') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="prodi">Program Studi</label>
      <x-search-select id="prodi" name="prodi" :required="true" noun="program studi" :options="$studyPrograms"
        :value="old('prodi', $previous?->study_program)" :invalid="$errors->has('prodi')" />
      @error('prodi') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="periode">Periode KKN</label>
      <x-search-select id="periode" name="periode" :required="true" noun="periode" :options="$periods" placeholder="Mis. Periode 2 Tahun 2026"
        :value="old('periode', $previous?->kkn_period)" :invalid="$errors->has('periode')" />
      @error('periode') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="tema">Tema KKN</label>
      <x-search-select id="tema" name="tema" :required="true" noun="tema" :options="$themes"
        :value="old('tema', $previous?->kkn_theme)" :invalid="$errors->has('tema')" />
      <div class="hint">Periode dan tema ini otomatis terisi di form logbook. Bila keliru, bisa diubah nanti di menu Data KKN.</div>
      @error('tema') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
      <label for="catatan">Catatan untuk admin <span class="optional">(opsional)</span></label>
      <textarea id="catatan" name="catatan" maxlength="500" placeholder="Mis. nomor unit atau nama DPL.">{{ old('catatan', $previous?->note) }}</textarea>
      @error('catatan') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <button class="btn btn-primary btn-block" type="submit">Kirim Pendaftaran</button>
  </form>
@endif

<p class="auth-note"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
@endsection
