@extends('layouts.auth')

@section('title', 'Masuk - Logbook KKN UGM')

@section('content')
<h3>Selamat datang</h3>
<p class="sub">Masuk dengan akun UGM untuk mengisi presensi dan logbook, atau lihat-lihat dulu sebagai tamu.</p>

<a href="{{ route('auth.google') }}" class="btn btn-google btn-block">
  <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8Z"/><path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3c-1.1.7-2.5 1.2-4.1 1.2-3.1 0-5.8-2.1-6.7-5H1.3v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.3 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8l4-3.1Z"/><path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.3 6.6l4 3.1c.9-2.9 3.6-5 6.7-5Z"/></svg>
  Masuk dengan Akun UGM
</a>

<div class="auth-sep">atau</div>

<form method="post" action="{{ route('guest.enter') }}">
  @csrf
  <button type="submit" class="btn btn-outline btn-block">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
    Lihat sebagai Tamu
  </button>
</form>

<p class="auth-note">Mode tamu hanya bisa melihat data publik: pencarian mahasiswa, pencarian logbook, dan peta sebaran. Presensi dan input logbook butuh akun UGM.</p>

@if ($demoStudents->isNotEmpty())
  <details class="auth-demo">
    <summary>Coba dengan akun demo</summary>
    <div class="auth-demo-body">
      <p class="hint" style="margin:0 0 12px">Google OAuth belum dikonfigurasi di environment ini, jadi akun demo tersedia untuk mencoba semua peran.</p>
      <form method="post" action="{{ route('demo-login') }}">
        @csrf
        <div class="form-group">
          <label for="demo-email">Pilih akun</label>
          <select name="email" id="demo-email" required>
            @foreach ($demoStudents as $s)
              <option value="{{ $s->email }}">{{ $s->name }} &mdash; {{ $s->email }}</option>
            @endforeach
          </select>
        </div>
        <button class="btn btn-soft btn-block" type="submit">Masuk dengan Akun Demo</button>
      </form>
    </div>
  </details>
@endif
@endsection
