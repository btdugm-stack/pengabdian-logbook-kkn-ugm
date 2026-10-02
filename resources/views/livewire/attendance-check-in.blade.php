@section('title', 'Presensi Harian')
@section('description', 'Check-in, check-out, dan kondisi kesehatan hari ini.')

@php
  $conditions = [
    'Sehat' => '#059669',
    'Sakit Ringan' => '#E11D48',
    'Sakit Berat' => '#7F1D1D',
    'Izin' => '#D97706',
    'Alpha' => '#D97706',
  ];
@endphp

<div class="grid grid-2">
  <div class="card">
    <div class="card-head">
      <h2>Presensi Hari Ini</h2>
      <span class="hint" style="margin:0">{{ today()->translatedFormat('l, d F Y') }}</span>
    </div>

    @if ($this->today)
      <x-banner :tone="in_array($this->today->condition, ['Sakit Ringan', 'Sakit Berat']) ? 'danger' : (in_array($this->today->condition, ['Izin', 'Alpha']) ? 'warn' : 'success')"
        title="Check-in {{ $this->today->check_in_time->format('H.i') }}">
        {{ $this->today->check_out_time ? 'Check-out '.$this->today->check_out_time->format('H.i') : 'Belum check-out' }}
      </x-banner>
    @endif

    <div class="form-group" style="margin-top:18px">
      <label id="condition-label">Bagaimana kondisimu hari ini?</label>
      <div class="chip-row" role="radiogroup" aria-labelledby="condition-label">
        @foreach ($conditions as $name => $color)
          <button type="button" wire:key="condition-{{ $loop->index }}" role="radio" aria-checked="{{ $condition === $name ? 'true' : 'false' }}"
            class="chip @if($condition === $name) active @endif"
            style="@if($condition === $name) background:{{ $color }} @endif"
            wire:click="$set('condition', '{{ $name }}')">{{ $name }}</button>
        @endforeach
      </div>
      @error('condition') <div class="field-error">{{ $message }}</div> @enderror
      @if (in_array($condition, ['Sakit Ringan', 'Sakit Berat'], true))
        <p class="hint">Kormasit dan DPL-mu akan otomatis diberi tahu supaya bisa segera membantu.</p>
      @endif
    </div>

    @if ($condition !== 'Sehat')
      <div class="form-group">
        <label for="condition_note">Catatan Kondisi</label>
        <textarea id="condition_note" wire:model="condition_note" placeholder="Jelaskan kondisi/alasan singkat" maxlength="1000"></textarea>
        @error('condition_note') <div class="field-error">{{ $message }}</div> @enderror
      </div>
    @endif

    <div class="form-group">
      <label for="checkin-coordinate">Lokasi Check-in <span class="optional">(opsional)</span></label>
      <div class="master-inline">
        <input id="checkin-coordinate" wire:model="coordinate" placeholder="-7.7956, 110.3695" inputmode="decimal">
        <x-geo-button target="coordinate" />
      </div>
      @error('coordinate') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="hero-actions">
      <button class="btn btn-primary" type="button" wire:click="checkIn" wire:loading.attr="disabled" wire:target="checkIn">
        <span wire:loading.remove wire:target="checkIn">{{ $this->today ? 'Perbarui Presensi' : 'Check-in Sekarang' }}</span>
        <span wire:loading wire:target="checkIn">Menyimpan…</span>
      </button>
      @if ($this->today && ! $this->today->check_out_time)
        <button class="btn btn-outline" type="button" wire:click="checkOut" wire:loading.attr="disabled" wire:target="checkOut">Check-out</button>
      @endif
    </div>
  </div>

  <div class="card">
    <h2>Riwayat 14 Hari Terakhir</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Check-in</th><th>Check-out</th><th>Kondisi</th></tr></thead>
        <tbody>
          @forelse ($this->history as $h)
            <tr wire:key="history-{{ $h->id }}">
              <td class="nowrap">{{ $h->attendance_date->translatedFormat('d M Y') }}</td>
              <td>{{ $h->check_in_time?->format('H:i') ?? '-' }}</td>
              <td>{{ $h->check_out_time?->format('H:i') ?? '-' }}</td>
              <td class="nowrap"><x-pill :class="$h->conditionPillClass()">{{ $h->condition }}</x-pill></td>
            </tr>
          @empty
            <tr><td colspan="4" class="empty-cell">Belum ada riwayat presensi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
