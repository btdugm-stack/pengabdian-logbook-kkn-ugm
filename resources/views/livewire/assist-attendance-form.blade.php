@section('title', 'Input Presensi Bantuan')
@section('description', 'Catat bantuan Anda ke program kerja mahasiswa/sub-unit lain.')

<div class="card">
  <h2>Presensi RPP Bantuan</h2>
  <p>Catat bantuanmu ke program mahasiswa lain — host perlu menyetujui dulu sebelum tercatat resmi.</p>

  @error('program')
    <div class="alert alert-error">{{ $message }}</div>
  @enderror

  <div class="form-row">
    <div class="form-group">
      <label>Mahasiswa yang Dibantu (Host)</label>
      <select wire:model="host_student_id" required>
        <option value="">Pilih mahasiswa...</option>
        @foreach ($this->hostOptions as $s)
          <option value="{{ $s->id }}">{{ $s->name }} &mdash; {{ $s->faculty }}</option>
        @endforeach
      </select>
      @error('host_student_id') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label>Tanggal</label>
      <input type="date" wire:model="assist_date" required>
      @error('assist_date') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-group">
    <label>Program Kerja yang Dibantu</label>
    <div class="master-inline">
      <select wire:model="program_name">
        <option value="">Pilih program...</option>
        @foreach ($this->programs as $p)
          <option value="{{ $p->name }}">{{ $p->name }}</option>
        @endforeach
      </select>
      <input wire:model="program_new" placeholder="+ Program baru">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Jam Bantuan</label>
      <input type="number" step="0.5" min="0.5" max="24" wire:model="hours">
      @error('hours') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label>Catatan Peran</label>
      <input wire:model="role_note" placeholder="mis. dokumentasi, logistik, narasumber">
    </div>
  </div>

  <button class="btn btn-primary" type="button" wire:click="save" wire:loading.attr="disabled">Simpan Presensi Bantuan</button>
</div>
