@php
  // Admin atas nama mahasiswa tidak diblok presensi: ia mengoreksi data, termasuk hari lampau.
  $canSubmit = $logbook || $this->onBehalf || $this->todayAttendance;
@endphp
<div class="card">
  <div class="card-head">
    <h2>{{ match (true) { ! $logbook => 'Input Logbook Kegiatan', $logbook->isDraft() => 'Lanjutkan Draft Logbook', $logbook->needsRevision() => 'Perbaiki Logbook', default => 'Ubah Logbook' } }}</h2>
    @if ($logbook)
      <x-pill :class="$logbook->statusPillClass()">{{ $logbook->statusLabel() }}</x-pill>
    @endif
  </div>

  {{-- Ringkasan isian yang belum beres setelah Kirim/Simpan ditekan. Tiap nama
       menautkan ke kolomnya; wire:key berganti tiap render supaya blok ini
       digulir ke tampilan lagi pada percobaan kirim berikutnya. --}}
  @if ($this->invalidFields)
    <div class="alert alert-error" role="alert" wire:key="invalid-{{ microtime(true) }}"
      x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'center' })">
      Logbook belum tersimpan. Lengkapi atau perbaiki {{ count($this->invalidFields) }} isian berikut:
      <ul class="error-list">
        @foreach ($this->invalidFields as $field => $label)
          <li><a href="#{{ $field }}" x-on:click.prevent="document.getElementById('{{ $field }}')?.focus()">{{ $label }}</a>: {{ $errors->first($field) }}</li>
        @endforeach
      </ul>
    </div>
  @endif
  @error('attendance')
    <div class="alert alert-error" role="alert">{{ $message }}</div>
  @enderror

  @if ($this->onBehalf)
    <x-banner tone="warn" title="Anda mengisi atas nama {{ $this->owner->name }}">
      Logbook tercatat sebagai milik {{ $this->owner->name }}, dengan keterangan bahwa Anda yang menginputnya.
    </x-banner>
    <div style="height:12px"></div>
  @endif

  @if ($logbook?->needsRevision())
    <x-banner tone="danger" title="Pembimbing meminta revisi">
      <span class="prewrap">{{ $logbook->latestReview?->note ?? 'Tidak ada catatan.' }}</span>
    </x-banner>
    <div style="height:12px"></div>
  @endif

  @if ($logbook)
    <x-banner tone="warn" title="Kondisi saat logbook dibuat: {{ $logbook->health_status }}">
      Kondisi kesehatan tetap mengikuti presensi pada hari logbook ini dibuat.
    </x-banner>
  @elseif ($this->onBehalf)
    <x-banner tone="warn" title="Kondisi kesehatan mengikuti presensi pada tanggal kegiatan">
      Bila {{ $this->owner->name }} tidak presensi pada tanggal itu, kondisi dicatat sebagai "{{ \App\Models\Logbook::HEALTH_UNKNOWN }}".
    </x-banner>
  @elseif ($this->todayAttendance)
    @php
      $isSick = in_array($this->todayAttendance->condition, ['Sakit Ringan', 'Sakit Berat'], true);
    @endphp
    <x-banner :tone="$isSick ? 'danger' : 'success'" title="Kondisi hari ini: {{ $this->todayAttendance->condition }}">
      Otomatis dipakai sebagai kondisi kesehatan logbook ini.
    </x-banner>
  @else
    <x-banner tone="danger" title="Kamu belum presensi hari ini" action-label="Presensi Sekarang" :action-href="route('attendance.check-in')">
      Presensi dulu sebelum mengisi logbook.
    </x-banner>
  @endif

  @php($period = $logbook ? $logbook->kkn_period : $this->owner->kkn_period)
  <div class="form-group" style="margin-top:18px">
    <label for="kkn_period_display">Periode KKN</label>
    <input id="kkn_period_display" value="{{ $period ?: 'Belum diisi' }}" disabled>
    <div class="hint">
      @if ($logbook)
        Periode saat logbook ini dibuat.
      @elseif ($this->onBehalf)
        Diambil dari Data KKN {{ $this->owner->name }}.
      @else
        Terisi otomatis dari Data KKN, begitu juga tema di bawah. Bila keliru, <a class="table-link" href="{{ route('profile.edit') }}">ubah di Data KKN</a>.
      @endif
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label for="log_date">Tanggal &amp; Waktu Kegiatan</label>
      <input id="log_date" type="datetime-local" wire:model="log_date" @error('log_date') aria-invalid="true" @enderror max="{{ now()->format('Y-m-d\TH:i') }}" required>
      @error('log_date') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label for="community_count">Jumlah Warga Terlibat</label>
      <input id="community_count" type="number" inputmode="numeric" wire:model="community_count" min="0">
      @error('community_count') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  @if ($this->owner->isGroupLeader())
    <div class="form-group">
      <label class="checkbox" for="is_group">
        <input id="is_group" type="checkbox" wire:model="is_group">
        Logbook kelompok
      </label>
      <div class="hint">Centang bila ini kegiatan bersama kelompok yang {{ $this->onBehalf ? 'ketuanya laporkan' : 'kamu laporkan sebagai ketua' }}, bukan kegiatan pribadi.</div>
    </div>
  @endif

  <p class="hint" style="margin:0 0 12px">Tema, program, jenis, dan lokasi wajib diisi. Ketik untuk mencari di daftar; pilih yang sudah ada, atau tambahkan baru bila memang belum tersedia.</p>

  <div class="form-row">
    <div class="form-group">
      <label for="theme_name">Tema</label>
      <x-search-select id="theme_name" name-prop="theme_name" new-prop="theme_new" noun="tema"
        :options="$this->themes->pluck('name')" :value="$theme_new ?: $theme_name" :invalid="$errors->hasAny(['theme_name', 'theme_new'])" />
      @error('theme_name') <div class="field-error">{{ $message }}</div> @enderror
      @error('theme_new') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label for="program_name">Program Kerja</label>
      <x-search-select id="program_name" name-prop="program_name" new-prop="program_new" noun="program"
        :options="$this->programs->pluck('name')" :value="$program_new ?: $program_name" :invalid="$errors->hasAny(['program_name', 'program_new'])" />
      @error('program_name') <div class="field-error">{{ $message }}</div> @enderror
      @error('program_new') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label for="activity_type_name">Jenis Kegiatan</label>
      <x-search-select id="activity_type_name" name-prop="activity_type_name" new-prop="activity_type_new" noun="jenis kegiatan"
        :options="$this->activityTypes->pluck('name')" :value="$activity_type_new ?: $activity_type_name" :invalid="$errors->hasAny(['activity_type_name', 'activity_type_new'])" />
      @error('activity_type_name') <div class="field-error">{{ $message }}</div> @enderror
      @error('activity_type_new') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    <div class="form-group">
      <label for="location_name">Lokasi</label>
      <x-search-select id="location_name" name-prop="location_name" new-prop="location_new" noun="lokasi"
        :options="$this->locations->pluck('name')" :value="$location_new ?: $location_name" :invalid="$errors->hasAny(['location_name', 'location_new'])" />
      @error('location_name') <div class="field-error">{{ $message }}</div> @enderror
      @error('location_new') <div class="field-error">{{ $message }}</div> @enderror
    </div>
  </div>

  <div class="form-group">
    <label for="coordinate">Koordinat Lokasi <span class="optional">(opsional)</span></label>
    <div class="master-inline">
      <input @error('coordinate') aria-invalid="true" @enderror id="coordinate" wire:model="coordinate" placeholder="-7.7956, 110.3695" inputmode="decimal">
      <x-geo-button target="coordinate" />
    </div>
    <div class="hint">Bila koordinat terisi, Lokasi boleh dikosongkan: sistem memakai lokasi terdaftar dalam radius 50 m, atau mencatat titik koordinatnya.</div>
    @error('coordinate') <div class="field-error">{{ $message }}</div> @enderror
  </div>

  <div class="form-group">
    <label for="progress_note">Progress / Catatan Kegiatan</label>
    <textarea id="progress_note" wire:model="progress_note" @error('progress_note') aria-invalid="true" @enderror placeholder="Apa yang dikerjakan hari ini, hasilnya, dan rencana berikutnya..." maxlength="5000" required></textarea>
    @error('progress_note') <div class="field-error">{{ $message }}</div> @enderror
  </div>
  <div class="form-group">
    <label for="personal_info">Catatan Pribadi <span class="optional">(opsional, tidak tampil di publik)</span></label>
    <textarea id="personal_info" wire:model="personal_info" placeholder="Kendala, kondisi pribadi, atau hal yang perlu diketahui DPL..." maxlength="5000"></textarea>
    @error('personal_info') <div class="field-error">{{ $message }}</div> @enderror
  </div>
  <div class="form-group">
    <label for="documentation">Tautan Dokumentasi <span class="optional">(opsional)</span></label>
    <input id="documentation" wire:model="documentation" placeholder="https://drive.google.com/..." maxlength="255">
    @error('documentation') <div class="field-error">{{ $message }}</div> @enderror
  </div>

  <div class="hero-actions">
    @if ($logbook && ! $logbook->isDraft())
      {{-- Logbook yang sudah pernah dikirim tidak kembali jadi draft, jadi tidak ada pilihan draft. --}}
      <button class="btn btn-primary" type="button" wire:click="save('submit')" wire:loading.attr="disabled" wire:target="save">
        <span wire:loading.remove wire:target="save">{{ $logbook->needsRevision() ? 'Kirim Perbaikan' : 'Simpan Perubahan' }}</span>
        <span wire:loading wire:target="save">Menyimpan…</span>
      </button>
    @else
      <button class="btn btn-primary" type="button" wire:click="save('submit')" wire:loading.attr="disabled" wire:target="save" @disabled(! $canSubmit)>
        <span wire:loading.remove wire:target="save">Kirim Logbook</span>
        <span wire:loading wire:target="save">Menyimpan…</span>
      </button>
      <button class="btn btn-outline" type="button" wire:click="save('draft')" wire:loading.attr="disabled" wire:target="save" @disabled(! $canSubmit)>Simpan Draft</button>
    @endif
    @if ($this->onBehalf)
      <a class="btn btn-outline" href="{{ route('overview.student', $this->owner) }}">Batal</a>
    @elseif ($logbook)
      <a class="btn btn-outline" href="{{ route('logbooks.index') }}">Batal</a>
    @endif
  </div>
</div>
