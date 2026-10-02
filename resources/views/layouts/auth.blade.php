<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', 'Logbook KKN UGM')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#003D7C">
  <meta name="description" content="Presensi harian, logbook kegiatan, dan pemantauan wilayah KKN-PPM Universitas Gadjah Mada.">
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
  <meta name="sw-url" content="{{ asset('sw.js') }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Logbook KKN">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="auth-shell">
  <aside class="auth-aside">
    <div class="auth-brand">
      <div class="logo"><img src="{{ asset('icons/logo-ugm.png') }}" alt="Logo Universitas Gadjah Mada"></div>
      <div>
        <h1>Logbook KKN <span class="ea-tag">Early Access</span></h1>
        <p>KKN-PPM Universitas Gadjah Mada</p>
      </div>
    </div>

    <div class="auth-pitch">
      <h2>Catat kegiatan KKN, <em>Mudah dan Cepat</em>.</h2>
      <p>Presensi harian, logbook kegiatan, dan pemantauan wilayah dalam satu tempat bisa diakses langsung dari lapangan.</p>

      <div class="auth-points">
        <div class="auth-point">
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span>
          Presensi sekali ketuk dan monitoring kondisi kesehatan
        </div>
        <div class="auth-point">
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span>
          Logbook & peta sebaran kegiatan
        </div>
        <div class="auth-point">
          <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span>
          Pantauan per sub-unit untuk kormasit & DPL
        </div>
      </div>
    </div>

    <p class="auth-foot">Direktorat Pengabdian kepada Masyarakat &middot; Biro Transformasi Digital &middot; Universitas Gadjah Mada</p>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      {{-- Logo ringkas untuk layar sempit, karena panel biru di kiri disembunyikan di bawah 900px. --}}
      <div class="auth-mobile-brand">
        <div class="logo"><img src="{{ asset('icons/logo-ugm.png') }}" alt=""></div>
        <div>
          <strong>Logbook KKN UGM</strong>
          <span class="ea-tag">Early Access</span>
        </div>
      </div>

      @if (session('flash_success'))
        <div class="alert alert-success" role="status">{{ session('flash_success') }}</div>
      @endif
      @if (session('flash_error'))
        <div class="alert alert-error" role="alert">{{ session('flash_error') }}</div>
      @endif

      @yield('content')
    </div>
  </main>
</div>
{{-- Alpine (dibawa Livewire) dipakai kolom cari-pilih-tambah di form pendaftaran. --}}
@livewireScripts
</body>
</html>
