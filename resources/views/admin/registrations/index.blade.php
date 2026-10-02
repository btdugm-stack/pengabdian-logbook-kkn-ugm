@extends('layouts.app')

@section('title', 'Permintaan Pendaftaran')
@section('description', 'Pendaftaran mandiri dari pemilik akun Google UGM yang menunggu keputusan Anda.')

@section('content')
<div class="card">
  <div class="card-head">
    <h2>Menunggu Persetujuan ({{ $pending->count() }})</h2>
    <a class="btn btn-outline" href="{{ route('admin.participants.index') }}"><x-icon name="users" :size="16" /> Kelola Peserta</a>
  </div>

  @if ($pending->isEmpty())
    <p class="muted" style="margin:0">Tidak ada permintaan yang menunggu. Pendaftar baru akan muncul di sini setelah mengisi formulir Daftar di halaman masuk.</p>
  @endif

  <datalist id="region-paths">
    @foreach ($regionPaths as $path)
      <option value="{{ $path }}"></option>
    @endforeach
  </datalist>

  @foreach ($pending as $item)
    @php($bag = $errors->getBag('request_'.$item->id))
    <div class="request-item">
      <dl class="detail-list">
        <div><dt>Nama</dt><dd>{{ $item->name }}</dd></div>
        <div><dt>Email</dt><dd>{{ $item->email }}</dd></div>
        <div><dt>Peran diminta</dt><dd>{{ $item->requestedRoleLabel() }}</dd></div>
        <div><dt>Wilayah diminta</dt><dd>{{ $item->region_path ?? '-' }}</dd></div>
        <div><dt>Fakultas / Prodi</dt><dd>{{ $item->faculty ?: '-' }}{{ $item->study_program ? ' / '.$item->study_program : '' }}</dd></div>
        <div><dt>Periode KKN</dt><dd>{{ $item->kkn_period ?: '-' }}</dd></div>
        <div><dt>Tema KKN</dt><dd>{{ $item->kkn_theme ?: '-' }}</dd></div>
        @if ($item->note)
          <div><dt>Catatan</dt><dd>{{ $item->note }}</dd></div>
        @endif
        <div><dt>Dikirim</dt><dd>{{ $item->updated_at->translatedFormat('d M Y H:i') }}</dd></div>
      </dl>

      <div class="request-actions">
        <form method="post" action="{{ route('admin.registrations.approve', $item) }}">
          @csrf
          <div class="form-group">
            <label for="peran-{{ $item->id }}">Peran yang diberikan</label>
            <select id="peran-{{ $item->id }}" name="peran" required>
              @foreach (\Illuminate\Support\Arr::only(\App\Models\Student::ROLE_LABELS, auth()->user()->assignableRoles()) as $value => $label)
                <option value="{{ $value }}" @selected($item->requested_role === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @if ($bag->has('peran')) <div class="field-error">{{ $bag->first('peran') }}</div> @endif
          </div>
          <div class="form-group">
            <label for="wilayah-{{ $item->id }}">Wilayah</label>
            <input id="wilayah-{{ $item->id }}" name="wilayah" list="region-paths" value="{{ $item->region_path }}" maxlength="600">
            <div class="hint">Periksa ejaannya: wilayah yang belum ada akan dibuat baru. Kosongkan untuk peran admin.</div>
            @if ($bag->has('wilayah')) <div class="field-error">{{ $bag->first('wilayah') }}</div> @endif
          </div>
          <button class="btn btn-primary" type="submit"><x-icon name="check" :size="16" /> Setujui</button>
        </form>

        <form method="post" action="{{ route('admin.registrations.reject', $item) }}">
          @csrf
          <div class="form-group">
            <label for="alasan-{{ $item->id }}">Alasan penolakan <span class="optional">(opsional, terlihat oleh pendaftar)</span></label>
            <input id="alasan-{{ $item->id }}" name="alasan" maxlength="500" placeholder="Mis. bukan peserta periode ini.">
            @if ($bag->has('alasan')) <div class="field-error">{{ $bag->first('alasan') }}</div> @endif
          </div>
          <button class="btn btn-danger-outline" type="submit">Tolak</button>
        </form>
      </div>
    </div>
  @endforeach
</div>

@if ($processed->isNotEmpty())
  <div class="card" style="margin-top:18px">
    <h2>Riwayat Terakhir</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nama</th><th>Peran diminta</th><th>Keputusan</th><th>Oleh</th><th>Waktu</th></tr></thead>
        <tbody>
          @foreach ($processed as $item)
            <tr>
              <td><strong>{{ $item->name }}</strong><br><small class="muted">{{ $item->email }}</small></td>
              <td>{{ $item->requestedRoleLabel() }}</td>
              <td>
                @if ($item->status === \App\Models\RegistrationRequest::STATUS_APPROVED)
                  <x-pill class="pill-normal">Disetujui</x-pill>
                @else
                  <x-pill class="pill-warn">Ditolak</x-pill>
                  @if ($item->rejection_reason)<br><small class="muted">{{ $item->rejection_reason }}</small>@endif
                @endif
              </td>
              <td>{{ $item->reviewer?->name ?? '-' }}</td>
              <td class="nowrap">{{ $item->reviewed_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
