@props([
  'logbooks',
  // Mode publik: tanpa kolom kondisi kesehatan.
  'publicSafe' => false,
  'showStudent' => true,
  // Tombol Lanjutkan/Ubah/Perbaiki/Hapus untuk logbook milik sendiri yang belum diputuskan final.
  'ownerActions' => false,
  // Tautan ke halaman reviu untuk supervisor peran reviewer.
  'reviewable' => false,
  // Tautan koreksi untuk admin (input/ubah atas nama mahasiswa).
  'adminActions' => false,
])
@php
  $hasActions = $ownerActions || $reviewable || $adminActions;
  $columnCount = 6 + ($showStudent ? 1 : 0) + ($publicSafe ? 0 : 1) + ($hasActions ? 1 : 0);
@endphp
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Tanggal</th>
      @if ($showStudent)
        <th>Mahasiswa</th>
      @endif
      <th>Tema / Program</th><th>Lokasi</th><th>Warga</th>
      @unless ($publicSafe)
        <th>Kondisi</th>
      @endunless
      <th>Status</th><th>Catatan</th>
      @if ($hasActions)
        <th>Aksi</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @forelse ($logbooks as $l)
      <tr>
        <td class="nowrap">{{ $l->log_date->translatedFormat('d M Y') }}<br><small class="muted">{{ $l->log_date->format('H:i') }}</small></td>
        @if ($showStudent)
          <td>
            <strong>{{ $l->student->name }}</strong>
            @if ($l->student->faculty)
              <br><small class="muted">{{ $l->student->faculty }}@if ($l->student->study_program) &middot; {{ $l->student->study_program }}@endif</small>
            @endif
          </td>
        @endif
        <td>
          <strong>{{ $l->theme->name }}</strong><br>{{ $l->program->name }}<br><small class="muted">{{ $l->activityType->name }}</small>
          @if ($l->is_group)
            <br><x-pill class="pill-info">Kelompok</x-pill>
          @endif
          @if ($hasActions && $l->entered_by)
            <br><small class="muted">Diinput admin{{ $l->enteredBy ? ': '.$l->enteredBy->name : '' }}</small>
          @endif
        </td>
        <td>{{ $l->location->name }}</td>
        <td>{{ $l->community_count }}</td>
        @unless ($publicSafe)
          <td class="nowrap"><x-pill :class="$l->healthPillClass()">{{ $l->health_status }}</x-pill></td>
        @endunless
        <td>
          <x-pill :class="$l->statusPillClass()">{{ $l->statusLabel() }}</x-pill>
          {{-- Catatan pembimbing hanya untuk pemilik dan reviewer, tidak pernah di tabel publik. --}}
          @if ($hasActions && $l->latestReview?->note && ($l->needsRevision() || $l->status === \App\Models\Logbook::STATUS_REJECTED))
            <br><small class="review-note" title="{{ $l->latestReview->note }}">Catatan pembimbing: {{ \Illuminate\Support\Str::limit($l->latestReview->note, 110) }}</small>
          @endif
        </td>
        <td class="note-cell"><span title="{{ $l->progress_note }}">{{ \Illuminate\Support\Str::limit($l->progress_note, 160) }}</span></td>
        @if ($hasActions)
          <td class="nowrap">
            @if ($ownerActions && ! $l->isFinal())
              <div class="btn-row">
                <a class="btn btn-soft" href="{{ route('logbooks.edit', $l) }}">{{ match (true) { $l->isDraft() => 'Lanjutkan', $l->needsRevision() => 'Perbaiki', default => 'Ubah' } }}</a>
                @unless ($l->needsRevision())
                  <form method="post" action="{{ route('logbooks.destroy', $l) }}" onsubmit="return confirm('{{ $l->isDraft() ? 'Hapus draft logbook ini?' : 'Hapus logbook yang sudah dikirim ini? Logbook juga hilang dari pencarian publik dan panel pembimbing.' }} Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('delete')
                    <button class="btn btn-danger-outline" type="submit">Hapus</button>
                  </form>
                @endunless
              </div>
            @elseif ($ownerActions)
              <small class="muted" title="Logbook yang sudah disetujui atau ditolak pembimbing tidak bisa diubah atau dihapus.">Terkunci</small>
            @elseif ($reviewable || $adminActions)
              <div class="btn-row">
                @if ($reviewable && ! $l->isDraft())
                  <a class="btn {{ $l->isSubmitted() ? 'btn-primary' : 'btn-soft' }}" href="{{ route('logbooks.reviews.show', $l) }}">{{ $l->isSubmitted() ? 'Reviu' : 'Lihat' }}</a>
                @endif
                @if ($adminActions && ! $l->isFinal())
                  <a class="btn btn-outline" href="{{ route('admin.logbooks.edit', $l) }}">Koreksi</a>
                @endif
              </div>
            @else
              <small class="muted">&ndash;</small>
            @endif
          </td>
        @endif
      </tr>
    @empty
      <tr><td colspan="{{ $columnCount }}" class="empty-cell">Belum ada logbook.</td></tr>
    @endforelse
  </tbody>
</table>
</div>
