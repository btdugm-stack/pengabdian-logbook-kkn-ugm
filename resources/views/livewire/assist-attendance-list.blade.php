@section('title', 'Presensi Bantuan')
@section('description', 'Bantuan yang Anda berikan dan yang menunggu persetujuan Anda sebagai host.')

{{-- Dua tabel padat ini ditumpuk, bukan berdampingan: 6 dan 5 kolom
     berdempetan di grid-2 membuat kolom catatan/aksi tergencet. --}}
<div>
  <div class="card">
    <div class="card-head">
      <h2>Menunggu Persetujuan Saya</h2>
      <span class="hint" style="margin:0">{{ $this->pendingApproval->count() }} menunggu</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Program</th><th>Jam</th><th>Catatan</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($this->pendingApproval as $a)
            <tr>
              <td class="nowrap">{{ $a->assist_date->translatedFormat('d M Y') }}</td>
              <td>{{ $a->helper->name }}</td>
              <td>{{ $a->program->name }}</td>
              <td class="nowrap">{{ $a->hours }}</td>
              <td>{{ $a->role_note }}</td>
              <td>
                <div class="btn-row">
                  <button class="btn btn-primary" type="button" wire:click="approve({{ $a->id }})">Setujui</button>
                  <button class="btn btn-outline" type="button" wire:click="reject({{ $a->id }})">Tolak</button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6">Tidak ada yang menunggu persetujuan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" style="margin-top:18px">
    <div class="card-head">
      <h2>Bantuan yang Saya Berikan</h2>
      <a href="{{ route('assist-attendances.create') }}" class="btn btn-soft">+ Input Bantuan Baru</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Host</th><th>Program</th><th>Jam</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($this->given as $a)
            <tr>
              <td class="nowrap">{{ $a->assist_date->translatedFormat('d M Y') }}</td>
              <td>{{ $a->host->name }}</td>
              <td>{{ $a->program->name }}</td>
              <td class="nowrap">{{ $a->hours }}</td>
              <td class="nowrap"><x-pill :class="$a->statusPillClass()">{{ $a->approval_status }}</x-pill></td>
            </tr>
          @empty
            <tr><td colspan="5">Belum ada presensi bantuan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
