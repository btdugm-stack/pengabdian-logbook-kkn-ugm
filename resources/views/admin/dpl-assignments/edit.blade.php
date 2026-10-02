@extends('layouts.app')

@section('title', $own ? 'Mahasiswa Bimbingan' : 'Atur Bimbingan')
@section('description', $own ? 'Pilih mahasiswa yang Anda bimbing. Hanya mereka yang Anda pantau dan reviu logbooknya.' : $dpl->name.' · '.$dpl->email)

@section('content')
@unless ($own)
  <a href="{{ route('admin.dpl-assignments.index') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Penugasan DPL</a>
@endunless

<form method="post" action="{{ $own ? route('advisees.update') : route('admin.dpl-assignments.update', $dpl) }}" class="card"
  x-data="{
    query: '',
    count: {{ count($assignedIds) }},
    recount() { this.count = this.$root.querySelectorAll('input[name=\'mahasiswa[]\']:checked').length },
    matches(text) { return this.query.trim() === '' || text.toLowerCase().includes(this.query.trim().toLowerCase()) },
    toggleGroup(group, checked) { group.querySelectorAll('input[name=\'mahasiswa[]\']').forEach((box) => { if (box.closest('li').style.display !== 'none') box.checked = checked }); this.recount() },
  }">
  @csrf
  @method('put')

  <div class="card-head">
    <h2>Mahasiswa bimbingan <span x-text="'(' + count + ' dipilih)'">({{ count($assignedIds) }} dipilih)</span></h2>
    <button class="btn btn-primary" type="submit">{{ $own ? 'Simpan Pilihan' : 'Simpan Penugasan' }}</button>
  </div>

  <div class="form-group">
    <label for="cari-mahasiswa">Cari mahasiswa atau kelompok</label>
    <input id="cari-mahasiswa" type="search" x-model="query" placeholder="Ketik nama mahasiswa atau wilayah..." autocomplete="off">
    <div class="hint">Centang per mahasiswa, atau pakai "Pilih semua" untuk {{ $own ? 'mengambil' : 'menugaskan' }} satu kelompok sekaligus. Satu mahasiswa boleh punya lebih dari satu DPL.{{ $own ? ' Melepas centang berarti logbook mahasiswa itu tidak lagi masuk antrean reviu Anda.' : '' }}</div>
    @error('mahasiswa.*') <div class="field-error">Ada pilihan yang bukan peserta KKN. Muat ulang halaman lalu coba lagi.</div> @enderror
  </div>

  @forelse ($groups as $path => $students)
    <fieldset class="assign-group" x-ref="group{{ $loop->index }}" x-show="matches(@js($path)) || @js($students->pluck('name')->all()).some((name) => matches(name))">
      <legend>
        <span>{{ $path }} <small class="muted">· {{ $students->count() }} peserta</small></span>
        <span class="btn-row">
          <button type="button" class="btn btn-soft" x-on:click="toggleGroup($refs.group{{ $loop->index }}, true)">Pilih semua</button>
          <button type="button" class="btn btn-outline" x-on:click="toggleGroup($refs.group{{ $loop->index }}, false)">Lepas semua</button>
        </span>
      </legend>
      <ul class="assign-list">
        @foreach ($students as $student)
          @php($others = $student->advisors->where('id', '!=', $dpl->id)->pluck('name'))
          <li x-show="matches(@js($path)) || matches(@js($student->name))">
            <label class="checkbox">
              <input type="checkbox" name="mahasiswa[]" value="{{ $student->id }}" @checked(in_array($student->id, old('mahasiswa', $assignedIds))) x-on:change="recount()">
              <span>
                {{ $student->name }}
                @if ($student->isGroupLeader()) <x-pill class="pill-info">{{ $student->roleLabel() }}</x-pill> @endif
                <br><small class="muted">{{ $student->faculty ?: 'Fakultas belum diisi' }}{{ $others->isNotEmpty() ? ' · DPL lain: '.$others->implode(', ') : '' }}</small>
              </span>
            </label>
          </li>
        @endforeach
      </ul>
    </fieldset>
  @empty
    <p class="muted" style="margin:0">Belum ada peserta KKN terdaftar. Daftarkan mahasiswa dulu lewat Kelola Peserta.</p>
  @endforelse
</form>
@endsection
