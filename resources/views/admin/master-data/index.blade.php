@extends('layouts.app')

@section('title', 'Master Data')
@section('description', $canManage ? 'Rapikan pilihan tema, program, jenis kegiatan, dan lokasi yang dipakai di logbook.' : 'Daftar tema, program, jenis kegiatan, dan lokasi yang dipakai di logbook. Hanya lihat.')

@section('content')
<div class="card">
  <div class="chip-row" style="margin-bottom:16px">
    @foreach ($types as $key => $label)
      <a class="chip {{ $type === $key ? 'is-current' : '' }}" href="{{ route('admin.master-data.index', ['jenis' => $key]) }}" @if ($type === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
  </div>

  <div class="card-head">
    <h2>{{ $types[$type] }} ({{ $items->count() }})</h2>
  </div>
  @if ($canManage)
    <p class="hint" style="margin:0 0 12px">Data baru bertambah saat mahasiswa mengetiknya di form logbook. Di sini Anda merapikannya: ubah nama, gabungkan yang dobel, atau hapus yang tidak dipakai.</p>
  @endif

  <div class="table-wrap">
    <table>
      <thead><tr><th>Nama</th><th>Dipakai</th>@if ($canManage)<th>Ubah nama</th><th>Gabungkan ke</th><th></th>@endif</tr></thead>
      <tbody>
        @forelse ($items as $item)
          @php($bag = $errors->getBag('item_'.$item->id))
          <tr>
            <td><strong>{{ $item->name }}</strong></td>
            <td class="nowrap">{{ $item->logbooks_count }} logbook</td>
            @if ($canManage)
              <td>
                <form method="post" action="{{ route('admin.master-data.update', [$type, $item->id]) }}" class="inline-form">
                  @csrf
                  @method('put')
                  <input name="nama" value="{{ $item->name }}" maxlength="150" required aria-label="Nama baru untuk {{ $item->name }}">
                  <button class="btn btn-soft btn-sm" type="submit">Simpan</button>
                </form>
                @if ($bag->has('nama')) <div class="field-error">{{ $bag->first('nama') }}</div> @endif
              </td>
              <td>
                @if ($items->count() > 1)
                  <form method="post" action="{{ route('admin.master-data.merge', [$type, $item->id]) }}" class="inline-form"
                    onsubmit="return confirm('Gabungkan &quot;{{ $item->name }}&quot; ke data yang dipilih? Semua logbook dipindah dan data ini dihapus. Tidak bisa dibatalkan.')">
                    @csrf
                    <select name="tujuan" required aria-label="Gabungkan {{ $item->name }} ke">
                      <option value="">Pilih tujuan...</option>
                      @foreach ($items as $other)
                        @continue($other->id === $item->id)
                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                      @endforeach
                    </select>
                    <button class="btn btn-outline btn-sm" type="submit">Gabungkan</button>
                  </form>
                  @if ($bag->has('tujuan')) <div class="field-error">{{ $bag->first('tujuan') }}</div> @endif
                @endif
              </td>
              <td class="nowrap">
                @if ($item->logbooks_count === 0)
                  <form method="post" action="{{ route('admin.master-data.destroy', [$type, $item->id]) }}" onsubmit="return confirm('Hapus &quot;{{ $item->name }}&quot;?')">
                    @csrf
                    @method('delete')
                    <button class="btn btn-danger-outline btn-sm" type="submit">Hapus</button>
                  </form>
                @endif
              </td>
            @endif
          </tr>
        @empty
          <tr><td colspan="{{ $canManage ? 5 : 2 }}" class="empty-cell">Belum ada data. Data bertambah saat mahasiswa mengisi logbook pertama.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
