@extends('layouts.app')

@section('title', 'Logbook dalam Cakupan')
@section('description', 'Cakupan: '.$scopeLabel.'.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Cari Logbook</h2>
    <a class="btn btn-outline" href="{{ route('logbooks.browse.export', request()->query()) }}"><x-icon name="download" :size="16" /> {{ $aggregateOnly ? 'Export Rekap Eksekutif' : 'Export CSV' }}</a>
  </div>

  <form method="get" class="form-row-4">
    <div class="form-group">
      <label for="q">Kata kunci</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="Mahasiswa, tema, program, lokasi, atau isi catatan" maxlength="100">
    </div>
    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Semua status</option>
        @foreach ($statuses as $value => $label)
          <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label for="jenis">Jenis</label>
      <select id="jenis" name="jenis">
        <option value="">Pribadi dan kelompok</option>
        <option value="pribadi" @selected($kind === 'pribadi')>Pribadi</option>
        <option value="kelompok" @selected($kind === 'kelompok')>Kelompok</option>
      </select>
    </div>
    <div class="form-group">
      <button class="btn btn-primary">Tampilkan</button>
    </div>
  </form>

  @if ($aggregateOnly)
    <p class="hint" style="margin:0 0 12px">Tampilan strategis: hanya logbook yang menunggu reviu atau sudah disetujui, tanpa kondisi kesehatan. Export berisi rekap per tema dan program, bukan baris per mahasiswa.</p>
  @endif

  <x-logbook-table :logbooks="$logbooks" :public-safe="$aggregateOnly" :reviewable="$canReview" :admin-actions="$canCorrect" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
