@section('title', 'Presensi Bantuan')
@section('description', 'Bantuan yang Anda berikan dan yang menunggu persetujuan Anda sebagai host.')

<div class="grid grid-2">
  <div class="card">
    <h2>Menunggu Persetujuan Saya</h2>
    <p>Presensi bantuan dari mahasiswa lain untuk program kerja Anda.</p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Program</th><th>Jam</th><th>Catatan</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($this->pendingApproval as $a)
            <tr>
              <td>{{ $a->assist_date->translatedFormat('d M Y') }}</td>
              <td>{{ $a->helper->name }}</td>
              <td>{{ $a->program->name }}</td>
              <td>{{ $a->hours }}</td>
              <td>{{ $a->role_note }}</td>
              <td>
                <div class="hero-actions" style="margin-top:0">
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

  <div class="card">
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
              <td>{{ $a->assist_date->translatedFormat('d M Y') }}</td>
              <td>{{ $a->host->name }}</td>
              <td>{{ $a->program->name }}</td>
              <td>{{ $a->hours }}</td>
              <td><x-pill :class="$a->statusPillClass()">{{ $a->approval_status }}</x-pill></td>
            </tr>
          @empty
            <tr><td colspan="5">Belum ada presensi bantuan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
