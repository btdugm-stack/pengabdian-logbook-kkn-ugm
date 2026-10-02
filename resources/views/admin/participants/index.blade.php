@extends('layouts.app')

@section('title', 'Kelola Peserta')
@section('description', 'Daftarkan akun mahasiswa dan pembimbing agar bisa masuk dengan akun Google UGM.')

@section('content')
<div class="grid grid-4">
  <x-kpi label="Total Akun" :value="number_format($roleCounts->sum(), 0, ',', '.')" />
  <x-kpi label="Mahasiswa" :value="number_format($roleCounts['mahasiswa'], 0, ',', '.')" />
  <x-kpi label="Pembimbing, Unit & Admin" :value="number_format($roleCounts->except('mahasiswa')->sum(), 0, ',', '.')" />
  <x-kpi label="Belum Pernah Login" :value="number_format($neverLoggedIn, 0, ',', '.')" tone="{{ $neverLoggedIn ? 'warn' : null }}" />
</div>

<div class="card" style="margin-top:18px">
  <div class="card-head">
    <h2>Daftar Akun</h2>
    <div class="head-actions">
      <a class="btn btn-outline" href="{{ route('admin.participants.import') }}"><x-icon name="upload" :size="16" /> Impor CSV</a>
      <a class="btn btn-primary" href="{{ route('admin.participants.create') }}"><x-icon name="user-plus" :size="16" /> Tambah Akun</a>
    </div>
  </div>

  <form method="get" class="form-row-3">
    <div class="form-group">
      <label for="q">Cari</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="Nama atau email..." maxlength="100">
    </div>
    <div class="form-group">
      <label for="peran">Peran</label>
      <select id="peran" name="peran">
        <option value="">Semua peran</option>
        @foreach (\App\Models\Student::ROLE_LABELS as $value => $label)
          <option value="{{ $value }}" @selected($role === $value)>{{ $label }} ({{ $roleCounts[$value] }})</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <button class="btn btn-primary">Tampilkan</button>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Peran</th><th>Wilayah</th><th>Fakultas / Prodi</th><th>Login</th><th></th></tr></thead>
      <tbody>
        @forelse ($participants as $p)
          <tr>
            <td><strong>{{ $p->name }}</strong><br><small class="muted">{{ $p->email }}</small></td>
            <td class="nowrap"><x-pill class="pill-info">{{ $p->roleLabel() }}</x-pill></td>
            <td>
              @if ($p->hasAnyRole(\App\Models\Student::ASSIGNED_SCOPE_ROLES))
                <a class="table-link" href="{{ route('admin.dpl-assignments.edit', $p) }}">Atur bimbingan</a>
              @elseif ($p->hasAnyRole(\App\Models\Student::UNIT_ROLES))
                <small class="muted">Unit: {{ $p->faculty ?: 'belum diisi' }}</small>
              @elseif ($p->hasAnyRole(\App\Models\Student::FULL_ACCESS_ROLES))
                <small class="muted">Seluruh wilayah</small>
              @else
                {{ $p->region?->fullPath() ?? '-' }}
              @endif
            </td>
            <td>{{ $p->faculty ?: '-' }}@if ($p->study_program)<br><small class="muted">{{ $p->study_program }}</small>@endif</td>
            <td class="nowrap">
              @if ($p->google_id)
                <x-pill class="pill-normal">Sudah login</x-pill>
              @else
                <x-pill class="pill-warn">Belum login</x-pill>
              @endif
            </td>
            <td class="nowrap">
              @if (auth()->user()->canManageAccount($p))
                <a class="btn btn-soft btn-sm" href="{{ route('admin.participants.edit', $p) }}">Ubah</a>
                @if (auth()->user()->isSuperAdmin() && ! $p->is(auth()->user()))
                  <a class="btn btn-danger-outline btn-sm" href="{{ route('admin.participants.edit', $p) }}#hapus-akun">Hapus</a>
                @endif
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="empty-cell">
              {{ $q !== '' || $role !== '' ? 'Tidak ada akun yang cocok dengan pencarian.' : 'Belum ada akun. Mulai dengan Tambah Akun atau Impor CSV.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $participants->links() }}</div>
</div>
@endsection
