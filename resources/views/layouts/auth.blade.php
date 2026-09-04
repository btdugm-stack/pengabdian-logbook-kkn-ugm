<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', config('app.name'))</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#003D7C">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="icon" href="/icons/icon-192.png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
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
      <div class="logo"><img src="/icons/logo-ugm.png" alt="Logo Universitas Gadjah Mada"></div>
      <div>
        <h1>Logbook KKN</h1>
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
      @if (session('flash_success'))
        <div class="alert alert-success">{{ session('flash_success') }}</div>
      @endif
      @if (session('flash_error'))
        <div class="alert alert-error">{{ session('flash_error') }}</div>
      @endif

      @yield('content')
    </div>
  </main>
</div>
</body>
</html>
