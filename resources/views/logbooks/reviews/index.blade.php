@extends('layouts.app')

@section('title', 'Reviu Logbook')
@section('description', 'Logbook mahasiswa dalam cakupan Anda yang menunggu keputusan: setujui, minta revisi, atau tolak.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Menunggu Reviu ({{ $waiting->total() }})</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Dikirim</th><th>Mahasiswa</th><th>Tema / Program</th><th>Lokasi</th><th>Kondisi</th><th>Catatan Kegiatan</th><th></th></tr></thead>
      <tbody>
        @forelse ($waiting as $l)
          <tr>
            <td class="nowrap">{{ $l->updated_at->translatedFormat('d M Y') }}<br><small class="muted">{{ $l->updated_at->format('H:i') }}</small></td>
            <td><strong>{{ $l->student->name }}</strong><br><small class="muted">{{ $l->student->region?->name ?? '-' }}</small></td>
            <td><strong>{{ $l->theme->name }}</strong><br>{{ $l->program->name }}<br><small class="muted">{{ $l->activityType->name }}</small></td>
            <td>{{ $l->location->name }}</td>
            <td class="nowrap"><x-pill :class="$l->healthPillClass()">{{ $l->health_status }}</x-pill></td>
            <td class="note-cell">{{ \Illuminate\Support\Str::limit($l->progress_note, 160) }}</td>
            <td class="nowrap"><a class="btn btn-primary btn-sm" href="{{ route('logbooks.reviews.show', $l) }}">Reviu</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="empty-cell">Tidak ada logbook yang menunggu reviu. Logbook baru muncul di sini setelah mahasiswa mengirimnya.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $waiting->links() }}</div>
</div>

@if ($recent->isNotEmpty())
  <div class="card" style="margin-top:18px">
    <h2>Keputusan Terakhir Anda</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Waktu</th><th>Mahasiswa</th><th>Program</th><th>Keputusan</th><th>Catatan</th><th></th></tr></thead>
        <tbody>
          @foreach ($recent as $review)
            <tr>
              <td class="nowrap">{{ $review->created_at->translatedFormat('d M Y H:i') }}</td>
              <td>{{ $review->logbook->student->name }}</td>
              <td>{{ $review->logbook->program->name }}</td>
              <td class="nowrap"><x-pill :class="$review->decisionPillClass()">{{ $review->decisionLabel() }}</x-pill></td>
              <td class="note-cell">{{ $review->note ? \Illuminate\Support\Str::limit($review->note, 120) : '-' }}</td>
              <td class="nowrap"><a class="btn btn-soft btn-sm" href="{{ route('logbooks.reviews.show', $review->logbook) }}">Lihat</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
