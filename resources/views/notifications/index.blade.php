@extends('layouts.app')

@section('title', 'Notifikasi')
@section('description', 'Eskalasi kesehatan mahasiswa dalam cakupan wilayah Anda.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Notifikasi</h2>
    @if ($notifications->whereNull('read_at')->isNotEmpty())
      <form method="post" action="{{ route('notifications.mark-all-read') }}">
        @csrf
        <button class="btn btn-outline" type="submit">Tandai Semua Dibaca</button>
      </form>
    @endif
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Waktu</th><th>Mahasiswa</th><th>Kondisi</th><th>Catatan</th><th>Wilayah</th><th></th></tr></thead>
      <tbody>
        @forelse ($notifications as $n)
          <tr class="{{ $n->read_at ? 'is-read' : '' }}">
            <td class="nowrap">{{ $n->created_at->translatedFormat('d M Y H:i') }}</td>
            <td>
              @if (isset($n->data['student_id']))
                <a class="table-link" href="{{ route('overview.student', $n->data['student_id']) }}">{{ $n->data['student_name'] ?? '-' }}</a>
              @else
                {{ $n->data['student_name'] ?? '-' }}
              @endif
            </td>
            <td class="nowrap"><x-pill class="pill-sick">{{ $n->data['condition'] ?? '-' }}</x-pill></td>
            <td>{{ $n->data['condition_note'] ?? '-' }}</td>
            <td><small>{{ $n->data['region'] ?? '-' }}</small></td>
            <td class="nowrap">
              @unless ($n->read_at)
                <form method="post" action="{{ route('notifications.mark-read', $n->id) }}" class="btn-row">
                  @csrf
                  <button class="btn btn-outline" type="submit">Tandai Dibaca</button>
                </form>
              @else
                <small class="muted">Sudah dibaca</small>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-cell">Belum ada notifikasi. Eskalasi muncul di sini saat mahasiswa melaporkan kondisi sakit.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $notifications->links() }}</div>
</div>
@endsection
