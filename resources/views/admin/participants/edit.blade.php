@extends('layouts.app')

@section('title', 'Ubah Akun')
@section('description', $student->name.' · '.$student->email)

@section('content')
<a href="{{ route('admin.participants.index') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Daftar Akun</a>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head">
      <h2>Data Akun</h2>
      @if ($student->google_id)
        <x-pill class="pill-normal">Sudah pernah login</x-pill>
      @else
        <x-pill class="pill-warn">Belum pernah login</x-pill>
      @endif
    </div>
    <form method="post" action="{{ route('admin.participants.update', $student) }}">
      @csrf
      @method('put')
      @include('admin.participants.form', ['student' => $student])
      <div class="hero-actions">
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
        <a class="btn btn-outline" href="{{ route('admin.participants.index') }}">Batal</a>
        @if ($student->isParticipant())
          <a class="btn btn-soft" href="{{ route('overview.student', $student) }}">Lihat Aktivitas</a>
        @endif
      </div>
    </form>
  </div>

  @include('admin.participants.role-guide')
</div>

@if (auth()->user()->isSuperAdmin())
  <div class="card card-danger" id="hapus-akun" style="margin-top:18px">
    <h2>Hapus Akun</h2>
    @if ($deletable)
      <p>Menghapus <strong>{{ $student->name }}</strong> bersifat permanen dan tidak bisa dibatalkan. Yang ikut terhapus:</p>
      <dl class="detail-list">
        @foreach ($deletionImpact as $label => $count)
          <div><dt>{{ ucfirst($label) }}</dt><dd>{{ $count }}</dd></div>
        @endforeach
      </dl>
      <p>Logbook yang terhapus juga hilang dari pencarian publik dan peta. Bila akun ini hanya perlu dinonaktifkan dari perannya, ubah perannya saja di atas.</p>
      <form method="post" action="{{ route('admin.participants.destroy', $student) }}">
        @csrf
        @method('delete')
        <div class="form-group">
          <label for="konfirmasi_email">Ketik <code class="inline">{{ $student->email }}</code> untuk mengonfirmasi</label>
          <input id="konfirmasi_email" name="konfirmasi_email" autocomplete="off" required @error('konfirmasi_email', 'deletion') aria-invalid="true" @enderror>
          @error('konfirmasi_email', 'deletion') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <button class="btn btn-danger-outline" type="submit">Hapus Akun Permanen</button>
      </form>
    @else
      <p style="margin:0">Anda tidak bisa menghapus akun sendiri. Minta super admin lain bila akun ini memang perlu dihapus.</p>
    @endif
  </div>
@endif
@endsection
