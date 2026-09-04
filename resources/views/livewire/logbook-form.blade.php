<div class="card">
  <h2>Input Logbook KKN</h2>

  @error('master')
    <div class="alert alert-error">{{ $message }}</div>
  @enderror

  @if ($this->todayAttendance)
    <x-banner tone="success" title="Kondisi hari ini: {{ $this->todayAttendance->condition }}">
      Otomatis dipakai sebagai kondisi kesehatan logbook ini.
    </x-banner>
  @else
    <x-banner tone="danger" title="Kamu belum presensi hari ini" action-label="Presensi Sekarang" :action-href="route('attendance.check-in')">
      Presensi dulu sebelum mengisi logbook.
    </x-banner>
  @endif

  <div class="form-row" style="margin-top:18px">
    <div class="form-group">
      <label>Tanggal &amp; Waktu</label>
      <input type="datetime-local" wire:model="log_date" required>
      @error('log_date') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Tema</label>
      <div class="master-inline">
        <select wire:model="theme_name">
          <option value="">Pilih tema...</option>
          @foreach ($this->themes as $t)
            <option value="{{ $t->name }}">{{ $t->name }}</option>
          @endforeach
        </select>
        <input wire:model="theme_new" placeholder="+ Tema baru">
      </div>
    </div>
    <div class="form-group">
      <label>Program Kerja</label>
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
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Jenis Kegiatan</label>
      <div class="master-inline">
        <select wire:model="activity_type_name">
          <option value="">Pilih jenis...</option>
          @foreach ($this->activityTypes as $a)
            <option value="{{ $a->name }}">{{ $a->name }}</option>
          @endforeach
        </select>
        <input wire:model="activity_type_new" placeholder="+ Jenis baru">
      </div>
    </div>
    <div class="form-group">
      <label>Lokasi</label>
      <div class="master-inline">
        <select wire:model="location_name">
          <option value="">Pilih lokasi...</option>
          @foreach ($this->locations as $loc)
            <option value="{{ $loc->name }}">{{ $loc->name }}</option>
          @endforeach
        </select>
        <input wire:model="location_new" placeholder="+ Lokasi baru">
      </div>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Koordinat</label>
      <div class="master-inline">
        <input wire:model="coordinate" placeholder="-7.7956, 110.3695">
        <button type="button" class="btn btn-outline"
          x-on:click="navigator.geolocation.getCurrentPosition(p => $wire.set('coordinate', p.coords.latitude.toFixed(7) + ', ' + p.coords.longitude.toFixed(7)))">
          📍 Lokasi saat ini
        </button>
      </div>
    </div>
    <div class="form-group">
      <label>Jumlah Masyarakat Terlibat</label>
      <input type="number" wire:model="community_count" min="0">
    </div>
  </div>

  <div class="form-group">
    <label>Progress / Catatan Kegiatan</label>
    <textarea wire:model="progress_note" placeholder="Progress kegiatan hari ini..." required></textarea>
    @error('progress_note') <div class="field-error">{{ $message }}</div> @enderror
  </div>
  <div class="form-group">
    <label>Personal Info</label>
    <textarea wire:model="personal_info" placeholder="Kondisi pribadi, kendala, atau catatan penting..."></textarea>
  </div>
  <div class="form-group">
    <label>Dokumentasi</label>
    <input wire:model="documentation" placeholder="URL dokumentasi / nama file">
  </div>

  <div class="hero-actions">
    <button class="btn btn-outline" type="button" wire:click="save('draft')" wire:loading.attr="disabled" @disabled(! $this->todayAttendance)>Simpan Draft</button>
    <button class="btn btn-primary" type="button" wire:click="save('submit')" wire:loading.attr="disabled" @disabled(! $this->todayAttendance)>Submit Logbook</button>
  </div>
</div>
