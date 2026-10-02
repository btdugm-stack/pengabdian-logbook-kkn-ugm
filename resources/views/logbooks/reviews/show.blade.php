@extends('layouts.app')

@section('title', 'Reviu Logbook')
@section('description', $logbook->student->name.' · '.$logbook->log_date->translatedFormat('d M Y H:i'))

@section('content')
<a href="{{ route('logbooks.reviews.index') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Antrean Reviu</a>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head">
      <h2>Isi Logbook</h2>
      <x-pill :class="$logbook->statusPillClass()">{{ $logbook->statusLabel() }}</x-pill>
    </div>
    <dl class="detail-list">
      <div><dt>Mahasiswa</dt><dd><a href="{{ route('overview.student', $logbook->student) }}">{{ $logbook->student->name }}</a><br><small class="muted">{{ $logbook->student->region?->fullPath() ?? '-' }}</small></dd></div>
      <div><dt>Tanggal Kegiatan</dt><dd>{{ $logbook->log_date->translatedFormat('d M Y H:i') }}</dd></div>
      <div><dt>Periode KKN</dt><dd>{{ $logbook->kkn_period ?: '-' }}</dd></div>
      <div><dt>Tema</dt><dd>{{ $logbook->theme->name }}</dd></div>
      <div><dt>Program Kerja</dt><dd>{{ $logbook->program->name }}</dd></div>
      <div><dt>Jenis Kegiatan</dt><dd>{{ $logbook->activityType->name }}</dd></div>
      <div><dt>Lokasi</dt><dd>{{ $logbook->location->name }}</dd></div>
      <div><dt>Warga Terlibat</dt><dd>{{ $logbook->community_count }}</dd></div>
      <div><dt>Kondisi Kesehatan</dt><dd><x-pill :class="$logbook->healthPillClass()">{{ $logbook->health_status }}</x-pill></dd></div>
      <div><dt>Catatan Kegiatan</dt><dd class="prewrap">{{ $logbook->progress_note }}</dd></div>
      @if ($logbook->personal_info)
        <div><dt>Catatan Pribadi</dt><dd class="prewrap">{{ $logbook->personal_info }}</dd></div>
      @endif
      <div>
        <dt>Dokumentasi</dt>
        <dd>
          @if (\Illuminate\Support\Str::startsWith($logbook->documentation, ['http://', 'https://']))
            <a href="{{ $logbook->documentation }}" target="_blank" rel="noopener noreferrer">Buka tautan dokumentasi</a>
          @else
            {{ $logbook->documentation ?: 'Tidak dilampirkan' }}
          @endif
        </dd>
      </div>
    </dl>
  </div>

  <div>
    <div class="card">
      <h2>Keputusan Reviu</h2>
      @if ($canDecide)
        <form method="post" action="{{ route('logbooks.reviews.store', $logbook) }}">
          @csrf
          <div class="form-group">
            <label for="catatan">Catatan untuk mahasiswa</label>
            <textarea id="catatan" name="catatan" maxlength="2000" placeholder="Wajib untuk revisi dan penolakan: apa yang perlu diperbaiki, atau alasan ditolak." @error('catatan') aria-invalid="true" @enderror>{{ old('catatan') }}</textarea>
            <div class="hint">Mahasiswa melihat catatan ini di Logbook Saya. Untuk persetujuan, catatan boleh dikosongkan.</div>
            @error('catatan') <div class="field-error">{{ $message }}</div> @enderror
            @error('keputusan') <div class="field-error">{{ $message }}</div> @enderror
          </div>
          <div class="hero-actions">
            <button class="btn btn-primary" type="submit" name="keputusan" value="setujui"><x-icon name="check" :size="16" /> Setujui</button>
            <button class="btn btn-outline" type="submit" name="keputusan" value="revisi">Minta Revisi</button>
            <button class="btn btn-danger-outline" type="submit" name="keputusan" value="tolak"
              onclick="return confirm('Tolak logbook ini? Penolakan bersifat final: mahasiswa tidak bisa memperbaiki atau mengirim ulang logbook ini.')">Tolak</button>
          </div>
        </form>
        <dl class="detail-list" style="margin-top:18px">
          <div><dt>Setujui</dt><dd>Logbook diterima dan dikunci.</dd></div>
          <div><dt>Minta Revisi</dt><dd>Dikembalikan ke mahasiswa dengan catatan Anda. Setelah diperbaiki, logbook masuk antrean ini lagi.</dd></div>
          <div><dt>Tolak</dt><dd>Final. Logbook dikunci dan tidak tampil di halaman publik.</dd></div>
        </dl>
      @elseif ($logbook->needsRevision())
        <p style="margin:0">Logbook ini sedang diperbaiki mahasiswa. Setelah dikirim ulang, logbook akan muncul lagi di antrean reviu.</p>
      @else
        <p style="margin:0">Logbook ini sudah {{ mb_strtolower($logbook->statusLabel()) }}. Keputusan ini final.</p>
      @endif
    </div>

    <div class="card" style="margin-top:16px">
      <h2>Riwayat Reviu</h2>
      @forelse ($reviews as $review)
        <div class="review-item">
          <div class="card-head" style="margin-bottom:6px">
            <x-pill :class="$review->decisionPillClass()">{{ $review->decisionLabel() }}</x-pill>
            <small class="muted">{{ $review->reviewer?->name ?? 'Akun terhapus' }} · {{ $review->created_at->translatedFormat('d M Y H:i') }}</small>
          </div>
          <p class="prewrap" style="margin:0">{{ $review->note ?: 'Tanpa catatan.' }}</p>
        </div>
      @empty
        <p class="muted" style="margin:0">Belum pernah direviu.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
