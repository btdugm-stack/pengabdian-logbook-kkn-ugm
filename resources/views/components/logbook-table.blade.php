@props(['logbooks', 'publicSafe' => false])
<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Tanggal</th><th>Mahasiswa</th><th>Tema/Program</th><th>Lokasi</th><th>Masyarakat</th>
      @unless ($publicSafe)
        <th>Kondisi</th>
      @endunless
      <th>Status</th><th>Catatan</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($logbooks as $l)
      <tr>
        <td>{{ $l->log_date->translatedFormat('d M Y H:i') }}</td>
        <td>
          <strong>{{ $l->student->name }}</strong>
          @unless ($publicSafe)
            <br><small>{{ $l->student->email }}</small>
          @endunless
          <br><small>{{ $l->student->faculty }} &middot; {{ $l->student->study_program }}</small>
        </td>
        <td><strong>{{ $l->theme->name }}</strong><br>{{ $l->program->name }}<br><small>{{ $l->activityType->name }}</small></td>
        <td>{{ $l->location->name }}<br><small>{{ $l->location->latitude }}, {{ $l->location->longitude }}</small></td>
        <td>{{ $l->community_count }}</td>
        @unless ($publicSafe)
          <td><x-pill :class="$l->healthPillClass()">{{ $l->health_status }}</x-pill></td>
        @endunless
        <td><x-pill :class="$l->statusPillClass()">{{ $l->status }}</x-pill></td>
        <td>{{ $l->progress_note }}</td>
      </tr>
    @empty
      <tr><td colspan="{{ $publicSafe ? 7 : 8 }}">Data tidak ditemukan.</td></tr>
    @endforelse
  </tbody>
</table>
</div>
