@section('title', 'Catat Presensi Bantuan')
@section('description', 'Catat bantuanmu ke program kerja mahasiswa/sub-unit lain.')

<div class="card">
  <h2>Presensi Bantuan</h2>
  <p>Host (pemilik program) perlu menyetujui dulu sebelum bantuanmu tercatat resmi.</p>

  @error('program')
    <div class="alert alert-error" role="alert">{{ $message }}</div>
  @enderror

  <div class="form-row">
    <div class="form-group">
      <label for="host_student_id">Mahasiswa yang Dibantu (Host)</label>
      <select id="host_student_id" wire:model="host_student_id" required>
        <option value="">Pilih mahasiswa...</option>
        @foreach ($this->hostOptions as $s)
          <option value="{{ $s->id }}" wire:key="host-{{ $s->id }}">{{ $s->name }}@if ($s->faculty) &mdash; {{ $s->faculty }}@endif</option>
        @endforeach
      </select>
      @error('host_student_id') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label for="assist_date">Tanggal</label>
      <input id="assist_date" type="date" wire:model="assist_date" max="{{ today()->toDateString() }}" required>
      @error('assist_date') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-group">
    <label for="assist_program_name">Program Kerja yang Dibantu</label>
    <x-search-select id="assist_program_name" name-prop="program_name" new-prop="program_new" noun="program"
      :options="$this->programs->pluck('name')" :value="$program_new ?: $program_name" :invalid="$errors->hasAny(['program', 'program_new'])" />
    <div class="hint">Ketik untuk mencari di daftar; pilih yang sudah ada, atau tambahkan baru bila memang belum tersedia.</div>
    @error('program_new') <div class="field-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-row">
    <div class="form-group">
      <label for="hours">Jam Bantuan</label>
      <input id="hours" type="number" step="0.5" min="0.5" max="24" inputmode="decimal" wire:model="hours">
      @error('hours') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label for="role_note">Catatan Peran <span class="optional">(opsional)</span></label>
      <input id="role_note" wire:model="role_note" placeholder="mis. dokumentasi, logistik, narasumber" maxlength="255">
      @error('role_note') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="hero-actions">
    <button class="btn btn-primary" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">Simpan Presensi Bantuan</button>
    <a class="btn btn-outline" href="{{ route('assist-attendances.index') }}">Batal</a>
  </div>
</div>
