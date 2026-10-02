@extends('layouts.app')

@section('title', 'Impor Akun dari CSV')
@section('description', 'Daftarkan atau perbarui banyak akun sekaligus.')

@section('content')
<a href="{{ route('admin.participants.index') }}" class="back-link"><x-icon name="arrow-left" :size="16" /> Kembali ke Daftar Akun</a>

@if (session('import_errors'))
  <div class="card card-danger" style="margin-bottom:18px">
    <h2>Baris yang perlu diperbaiki</h2>
    <p>Tidak ada data yang disimpan. Perbaiki baris berikut di file, lalu unggah ulang.</p>
    <div class="table-wrap scroll-y">
      <table>
        <thead><tr><th>Baris</th><th>Masalah</th></tr></thead>
        <tbody>
          @foreach (session('import_errors') as $line => $messages)
            <tr>
              <td class="nowrap"><strong>{{ $line }}</strong></td>
              <td>{{ implode(' ', $messages) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

<div class="grid grid-2">
  <div class="card">
    <h2>Unggah File</h2>
    <form method="post" action="{{ route('admin.participants.import.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label for="file">File CSV <span class="optional">(maks. 2 MB, {{ number_format($maxRows, 0, ',', '.') }} baris)</span></label>
        <input id="file" type="file" name="file" accept=".csv,text/csv" required>
        @error('file') <div class="field-error">{{ $message }}</div> @enderror
      </div>
      <label class="checkbox"><input type="checkbox" name="dry_run" value="1" checked> Cek dulu tanpa menyimpan</label>
      <p class="hint" style="margin:0 0 16px">Hilangkan centang setelah file dinyatakan valid untuk benar-benar mengimpor.</p>
      <div class="hero-actions">
        <button class="btn btn-primary" type="submit"><x-icon name="upload" :size="16" /> Unggah</button>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-head">
      <h2>Format File</h2>
      <a class="btn btn-soft btn-sm" href="{{ route('admin.participants.import.template') }}"><x-icon name="download" :size="15" /> Unduh Template</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Kolom</th><th>Isi</th></tr></thead>
        <tbody>
          <tr><td><code class="inline">email</code></td><td>Wajib. Email Google UGM yang dipakai login.</td></tr>
          <tr><td><code class="inline">nama</code></td><td>Wajib.</td></tr>
          <tr><td><code class="inline">peran</code></td><td>Wajib: @foreach (auth()->user()->assignableRoles() as $roleName)<code class="inline">{{ $roleName }}</code>@if (! $loop->last), @endif @endforeach</td></tr>
          <tr><td><code class="inline">wilayah</code></td><td>Path dipisah <code class="inline">/</code>, mis. Kabupaten Sleman / Kecamatan Ngaglik / Desa Candirejo / Sub-unit 5A. Kosong untuk admin.</td></tr>
          <tr><td><code class="inline">fakultas</code>, <code class="inline">prodi</code></td><td>Opsional.</td></tr>
        </tbody>
      </table>
    </div>
    <ul class="hint" style="margin:14px 0 0;padding-left:18px;line-height:1.7">
      <li>Pemisah koma atau titik koma (ekspor Excel) sama-sama diterima.</li>
      <li>Email yang sudah terdaftar akan diperbarui, bukan diduplikasi.</li>
      <li>Satu baris salah membatalkan seluruh impor, jadi tidak ada data setengah jadi.</li>
    </ul>
  </div>
</div>
@endsection
