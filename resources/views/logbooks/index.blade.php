@extends('layouts.app')

@section('title', 'Logbook Saya')
@section('description', 'Semua logbook kegiatanmu. Logbook bisa diubah selama belum disetujui atau ditolak pembimbing.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Logbook Saya</h2>
    <div class="head-actions" role="group" aria-label="Unduh logbook">
      <a href="{{ route('logbooks.export', ['format' => 'xlsx']) }}" class="btn btn-outline"><x-icon name="download" :size="16" /> Excel</a>
      <a href="{{ route('logbooks.export', ['format' => 'docx']) }}" class="btn btn-outline"><x-icon name="download" :size="16" /> Word</a>
      <a href="{{ route('logbooks.export', ['format' => 'csv']) }}" class="btn btn-outline"><x-icon name="download" :size="16" /> CSV</a>
    </div>
  </div>
  <x-logbook-table :logbooks="$logbooks" :show-student="false" :owner-actions="true" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
