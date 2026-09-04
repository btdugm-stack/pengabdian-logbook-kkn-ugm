@extends('layouts.app')

@section('title', 'Logbook Saya')
@section('description', 'Daftar logbook milik mahasiswa yang sedang login.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Logbook Saya</h2>
    <a href="{{ route('logbooks.export') }}" class="btn btn-outline">⬇️ Export CSV</a>
  </div>
  <x-logbook-table :logbooks="$logbooks" />
  <div class="pagination-wrap">{{ $logbooks->links() }}</div>
</div>
@endsection
