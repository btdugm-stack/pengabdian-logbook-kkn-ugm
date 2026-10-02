{{-- Field bersama form Tambah & Ubah Akun. Nama field = kolom CSV impor
     (lihat App\Support\ParticipantImporter::COLUMNS). $student null = akun baru. --}}
@php
  $currentRole = $student?->getRoleNames()->first();
  $isSelf = $student && $student->is(auth()->user());
@endphp

<div class="form-group">
  <label for="email">Email Google UGM</label>
  @if ($student)
    <input id="email" value="{{ $student->email }}" disabled>
    <div class="hint">Email adalah identitas login dan tidak bisa diubah. Bila keliru, daftarkan akun baru dengan email yang benar.</div>
  @else
    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@mail.ugm.ac.id" maxlength="150" required autofocus>
    <div class="hint">Harus sama persis dengan akun Google yang dipakai login: mahasiswa <code class="inline">@mail.ugm.ac.id</code>, dosen/tendik <code class="inline">@ugm.ac.id</code>.</div>
  @endif
  @error('email') <div class="field-error">{{ $message }}</div> @enderror
</div>

<div class="form-group">
  <label for="nama">Nama Lengkap</label>
  <input id="nama" name="nama" value="{{ old('nama', $student?->name) }}" maxlength="150" required>
  @error('nama') <div class="field-error">{{ $message }}</div> @enderror
</div>

<div class="form-group">
  <label for="peran_field">Peran</label>
  <select id="peran_field" name="peran" required @disabled($isSelf)>
    <option value="">Pilih peran...</option>
    @foreach (\Illuminate\Support\Arr::only(\App\Models\Student::ROLE_LABELS, auth()->user()->assignableRoles()) as $value => $label)
      <option value="{{ $value }}" @selected(old('peran', $currentRole) === $value)>{{ $label }}</option>
    @endforeach
  </select>
  @if ($isSelf)
    {{-- Select disabled tidak ikut terkirim - kirim peran saat ini lewat hidden input. --}}
    <input type="hidden" name="peran" value="{{ $currentRole }}">
    <div class="hint">Anda tidak bisa mengubah peran akun sendiri.</div>
  @endif
  @error('peran') <div class="field-error">{{ $message }}</div> @enderror
</div>

<div class="form-group">
  <label for="wilayah">Wilayah</label>
  <input id="wilayah" name="wilayah" list="region-paths" value="{{ old('wilayah', $student?->region?->fullPath()) }}" placeholder="Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A" maxlength="600">
  <datalist id="region-paths">
    @foreach ($regionPaths as $path)
      <option value="{{ $path }}"></option>
    @endforeach
  </datalist>
  <div class="hint">Pilih dari saran atau ketik baru; pisahkan tingkat dengan <code class="inline">/</code>. Wilayah baru dibuat otomatis. Kosongkan untuk DPL, Fakultas/Prodi, Pimpinan, dan admin.</div>
  @error('wilayah') <div class="field-error">{{ $message }}</div> @enderror
</div>

<div class="form-row">
  <div class="form-group">
    <label for="fakultas">Fakultas <span class="optional">(opsional; wajib untuk peran Fakultas/Prodi)</span></label>
    <input id="fakultas" name="fakultas" value="{{ old('fakultas', $student?->faculty) }}" maxlength="150">
    @error('fakultas') <div class="field-error">{{ $message }}</div> @enderror
  </div>
  <div class="form-group">
    <label for="prodi">Program Studi <span class="optional">(opsional)</span></label>
    <input id="prodi" name="prodi" value="{{ old('prodi', $student?->study_program) }}" maxlength="150">
    @error('prodi') <div class="field-error">{{ $message }}</div> @enderror
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label for="periode">Periode KKN <span class="optional">(opsional)</span></label>
    <input id="periode" name="periode" value="{{ old('periode', $student?->kkn_period) }}" maxlength="100" placeholder="Mis. Periode 2 Tahun 2026">
    @error('periode') <div class="field-error">{{ $message }}</div> @enderror
  </div>
  <div class="form-group">
    <label for="tema">Tema KKN <span class="optional">(opsional)</span></label>
    <input id="tema" name="tema" value="{{ old('tema', $student?->kkn_theme) }}" maxlength="150">
    <div class="hint">Periode dan tema menjadi isian otomatis di form logbook mahasiswa ini.</div>
    @error('tema') <div class="field-error">{{ $message }}</div> @enderror
  </div>
</div>
