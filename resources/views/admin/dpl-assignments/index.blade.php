@extends('layouts.app')

@section('title', 'Penugasan DPL')
@section('description', 'Tentukan mahasiswa bimbingan tiap DPL. DPL hanya mereviu dan memantau mahasiswa yang ditugaskan kepadanya.')

@section('content')
@if ($unassigned)
  <div style="margin-bottom:18px">
    <x-banner tone="warn" title="{{ $unassigned }} dari {{ $participants }} peserta belum punya DPL">
      Logbook mereka tidak masuk antrean reviu DPL mana pun sampai ditugaskan.
    </x-banner>
  </div>
@endif

<div class="card">
  <div class="card-head">
    <h2>Daftar DPL ({{ $dpls->count() }})</h2>
    <a class="btn btn-outline" href="{{ route('admin.participants.create') }}"><x-icon name="user-plus" :size="16" /> Tambah Akun DPL</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>DPL</th><th>Mahasiswa bimbingan</th><th></th></tr></thead>
      <tbody>
        @forelse ($dpls as $dpl)
          <tr>
            <td><strong>{{ $dpl->name }}</strong><br><small class="muted">{{ $dpl->email }}</small></td>
            <td>
              @if ($dpl->advisees_count)
                {{ $dpl->advisees_count }} mahasiswa
              @else
                <x-pill class="pill-warn">Belum ada bimbingan</x-pill>
              @endif
            </td>
            <td class="nowrap"><a class="btn btn-soft btn-sm" href="{{ route('admin.dpl-assignments.edit', $dpl) }}">Atur Bimbingan</a></td>
          </tr>
        @empty
          <tr><td colspan="3" class="empty-cell">Belum ada akun DPL. Daftarkan dulu lewat Kelola Peserta dengan peran DPL.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
