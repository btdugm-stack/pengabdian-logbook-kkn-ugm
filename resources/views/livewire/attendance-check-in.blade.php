@section('title', 'Presensi Harian')
@section('description', 'Check-in/check-out harian dengan kondisi kesehatan menyatu di dalamnya.')

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
    <h2>Presensi Hari Ini</h2>

    @if ($this->today)
      <x-banner :tone="in_array($this->today->condition, ['Sakit Ringan', 'Sakit Berat']) ? 'danger' : (in_array($this->today->condition, ['Izin', 'Alpha']) ? 'warn' : 'success')"
        title="Check-in {{ $this->today->check_in_time->format('H.i') }}">
        {{ $this->today->check_out_time ? 'Check-out '.$this->today->check_out_time->format('H.i') : 'Belum check-out' }}
      </x-banner>
    @endif

    <div class="form-group" style="margin-top:18px">
      <label>Bagaimana kondisimu hari ini?</label>
      <div class="chip-row">
        @foreach ($conditions as $name => $color)
          <button type="button" class="chip @if($condition === $name) active @endif"
            style="@if($condition === $name) background:{{ $color }} @endif"
            wire:click="$set('condition', '{{ $name }}')">{{ $name }}</button>
        @endforeach
      </div>
      @error('condition') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    @if ($condition !== 'Sehat')
      <div class="form-group">
        <label>Catatan Kondisi</label>
        <textarea wire:model="condition_note" placeholder="Jelaskan kondisi/alasan singkat"></textarea>
        @error('condition_note') <div class="field-error">{{ $message }}</div> @enderror
      </div>
    @endif

    <div class="form-group">
      <label>Lokasi Check-in</label>
      <div class="master-inline">
        <input wire:model="coordinate" placeholder="-7.7956, 110.3695">
        <button type="button" class="btn btn-outline"
          x-on:click="navigator.geolocation.getCurrentPosition(p => $wire.set('coordinate', p.coords.latitude.toFixed(7) + ', ' + p.coords.longitude.toFixed(7)))">
          📍 Lokasi saat ini
        </button>
      </div>
    </div>

    <div class="hero-actions">
      <button class="btn btn-primary" type="button" wire:click="checkIn" wire:loading.attr="disabled">
        {{ $this->today ? 'Perbarui Presensi' : 'Check-in Sekarang' }}
      </button>
      @if ($this->today && ! $this->today->check_out_time)
        <button class="btn btn-outline" type="button" wire:click="checkOut" wire:loading.attr="disabled">Check-out</button>
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
            <tr>
              <td>{{ $h->attendance_date->translatedFormat('d M Y') }}</td>
              <td>{{ $h->check_in_time?->format('H:i') ?? '-' }}</td>
              <td>{{ $h->check_out_time?->format('H:i') ?? '-' }}</td>
              <td><x-pill :class="$h->conditionPillClass()">{{ $h->condition }}</x-pill></td>
            </tr>
          @empty
            <tr><td colspan="4">Belum ada riwayat presensi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
