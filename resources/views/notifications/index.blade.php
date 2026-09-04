@extends('layouts.app')

@section('title', 'Notifikasi')
@section('description', 'Eskalasi kesehatan dan pemberitahuan lain untuk akun Anda.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Notifikasi</h2>
    <form method="post" action="{{ route('notifications.mark-all-read') }}">
      @csrf
      <button class="btn btn-outline" type="submit">Tandai Semua Dibaca</button>
    </form>
  </div>

  <div class="table-wrap" style="margin-top:14px">
    <table>
      <thead><tr><th>Waktu</th><th>Mahasiswa</th><th>Kondisi</th><th>Wilayah</th><th></th></tr></thead>
      <tbody>
        @forelse ($notifications as $n)
          <tr style="{{ $n->read_at ? 'opacity:.6' : '' }}">
            <td>{{ $n->created_at->translatedFormat('d M Y H:i') }}</td>
            <td>{{ $n->data['student_name'] ?? '-' }}</td>
            <td><x-pill class="pill-sick">{{ $n->data['condition'] ?? '-' }}</x-pill></td>
            <td><small>{{ $n->data['region'] ?? '-' }}</small></td>
            <td>
              @unless ($n->read_at)
                <form method="post" action="{{ route('notifications.mark-read', $n->id) }}">
                  @csrf
                  <button class="btn btn-outline" type="submit">Tandai Dibaca</button>
                </form>
              @else
                <small>Sudah dibaca</small>
              @endunless
            </td>
          </tr>
        @empty
          <tr><td colspan="5">Belum ada notifikasi.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-wrap">{{ $notifications->links() }}</div>
</div>
@endsection
