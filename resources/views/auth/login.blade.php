@extends('layouts.app')

@section('title', 'Login')
@section('description', 'Login mahasiswa via Google SSO (domain @' . config('services.google.allowed_domain') . ').')

@section('content')
<div class="grid grid-2">
  <div class="card">
    <h2>Login Mahasiswa</h2>
    <p>Gunakan akun Google kampus untuk masuk. Sesi Anda dipetakan ke data mahasiswa yang sudah terdaftar sebagai peserta KKN.</p>
    <a class="btn btn-primary" href="{{ route('auth.google') }}">Login dengan Google SSO</a>

    @if ($demoStudents->isNotEmpty())
      <hr style="margin:22px 0;border:none;border-top:1px solid var(--border)">
      <h3>Login Demo (khusus environment local)</h3>
      <p>Google OAuth belum dikonfigurasi di environment ini. Gunakan akun demo untuk mencoba alur mahasiswa.</p>
      <form method="post" action="{{ route('demo-login') }}">
        @csrf
        <div class="form-group">
          <label>Pilih Akun Demo</label>
          <select name="email" required>
            @foreach ($demoStudents as $s)
              <option value="{{ $s->email }}">{{ $s->name }} &mdash; {{ $s->email }}</option>
            @endforeach
          </select>
        </div>
        <button class="btn btn-outline" type="submit">Login Demo</button>
      </form>
    @endif
  </div>
  <div class="card">
    <h3>Flow Login</h3>
    <div class="flow-box"><strong>Google SSO</strong><br>Mahasiswa login menggunakan akun Google kampus.</div><br>
    <div class="flow-box"><strong>Verifikasi Domain &amp; Roster</strong><br>Email dicocokkan ke domain kampus dan data mahasiswa terdaftar.</div><br>
    <div class="flow-box"><strong>Dashboard</strong><br>Mahasiswa input data KKN dan logbook.</div>
  </div>
</div>
@endsection
