<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', config('app.name'))</title>
  {{-- viewport-fit=cover mengizinkan konten mengalir ke area notch/status bar
       bulat (iPhone dsb.); paddingnya ditangani lewat env(safe-area-inset-*)
       di resources/css/app.css, bukan dibiarkan konten mentok/terpotong. --}}
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#003D7C">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="icon" href="/icons/icon-192.png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  {{-- iOS mengabaikan sebagian besar manifest.webmanifest untuk "Add to Home
       Screen" - butuh meta khusus ini supaya jadi standalone app, bukan tab
       Safari biasa dengan chrome browser penuh. --}}
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Logbook KKN">
  {{-- Pasang state sidebar sebelum CSS dirender supaya tidak ada kedip lebar. --}}
  <script>
    try {
      if (localStorage.getItem('logbook-kkn:sidebar-collapsed') === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
      }
    } catch (e) {}
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
@php
  $activeUser = auth()->user();
  $isGuestMode = ! $activeUser && session('guest_mode');
  $initials = $activeUser
    ? strtoupper(collect(explode(' ', trim($activeUser->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''))
    : '';
  $roleLabel = $activeUser ? \Illuminate\Support\Str::headline($activeUser->getRoleNames()->first() ?? 'mahasiswa') : null;
  $subLabel = $activeUser ? ($activeUser->hasRole('mahasiswa') ? $activeUser->faculty : $activeUser->region?->name) : null;
@endphp
<div class="shell">
  <div class="drawer-overlay" id="drawer-overlay"></div>

  {{-- .sidebar (aside) cuma pembawa latar biru - dibuat height:auto supaya
       stretch mengikuti tinggi baris grid (= tinggi .content persis), jadi
       latarnya tidak pernah "bocor"/terputus di halaman panjang. Semua isi
       & perilaku sticky-nya ada di .sidebar-inner (lihat catatan di app.css). --}}
  <aside class="sidebar">
  <div class="sidebar-inner">
    <div class="brand">
      <div class="logo"><img src="/icons/logo-ugm.png" alt="Logo Universitas Gadjah Mada"></div>
      <div class="brand-text">
        <h1>Logbook KKN</h1>
        <p>KKN-PPM UGM</p>
      </div>
      <button type="button" class="sidebar-toggle" id="sidebar-toggle" title="Ciutkan / lebarkan menu" aria-label="Ciutkan atau lebarkan menu">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>

    @auth
      <div class="role-card">
        <div class="avatar">{{ $initials }}</div>
        <div style="min-width:0">
          <div class="name">{{ $activeUser->name }}</div>
          <div class="desc">{{ $roleLabel }}@if ($subLabel) &middot; {{ $subLabel }} @endif</div>
        </div>
      </div>
    @else
      <div class="role-card">
        <div class="avatar" style="background:rgba(255,255,255,.14);color:#fff">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
        </div>
        <div style="min-width:0">
          <div class="name">{{ $isGuestMode ? 'Mode Tamu' : 'Pengunjung' }}</div>
          <div class="desc">{{ $isGuestMode ? 'Akses data publik' : 'Belum masuk' }}</div>
        </div>
      </div>
    @endauth

    <div class="nav-group">
      <div class="nav-title">Menu Utama</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}" title="Beranda"><span class="icon">🏠</span> <span class="label">Beranda</span></a>

        @auth
          <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard"><span class="icon">📊</span> <span class="label">Dashboard</span></a>
          <a class="{{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" title="Data KKN"><span class="icon">👤</span> <span class="label">Data KKN</span></a>
          @hasrole('mahasiswa')
            <a class="{{ request()->routeIs('logbooks.create') ? 'active' : '' }}" href="{{ route('logbooks.create') }}" title="Input Logbook"><span class="icon">📝</span> <span class="label">Input Logbook</span></a>
            <a class="{{ request()->routeIs('logbooks.index') ? 'active' : '' }}" href="{{ route('logbooks.index') }}" title="Logbook Saya"><span class="icon">📚</span> <span class="label">Logbook Saya</span></a>
            <a class="{{ request()->routeIs('map.mine') ? 'active' : '' }}" href="{{ route('map.mine') }}" title="Peta Saya"><span class="icon">🗺️</span> <span class="label">Peta Saya</span></a>
          @endhasrole
        @endauth
      </nav>
    </div>

    @hasrole('mahasiswa')
    <div class="nav-group">
      <div class="nav-title">Presensi</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('attendance.check-in') ? 'active' : '' }}" href="{{ route('attendance.check-in') }}" title="Presensi Harian"><span class="icon">✅</span> <span class="label">Presensi Harian</span></a>
        <a class="{{ request()->routeIs('assist-attendances.*') ? 'active' : '' }}" href="{{ route('assist-attendances.index') }}" title="Presensi Bantuan"><span class="icon">🤝</span> <span class="label">Presensi Bantuan</span></a>
      </nav>
    </div>
    @endhasrole

    @hasanyrole(implode('|', \App\Models\Student::SUPERVISORY_ROLES))
    <div class="nav-group">
      <div class="nav-title">Panel Supervisi</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('overview') ? 'active' : '' }}" href="{{ route('overview') }}" title="Overview Wilayah"><span class="icon">🧭</span> <span class="label">Overview Wilayah</span></a>
        @php($unread = auth()->user()->unreadNotifications()->count())
        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}" title="Notifikasi">
          <span class="icon">🔔</span> <span class="label">Notifikasi</span>
          @if ($unread)
            <span class="badge">{{ $unread }}</span>
          @endif
        </a>
      </nav>
    </div>
    @endhasanyrole

    @guest
      <div class="nav-group">
        <div class="nav-title">Data Publik</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('search.students') ? 'active' : '' }}" href="{{ route('search.students') }}" title="Search Mahasiswa"><span class="icon">🔎</span> <span class="label">Search Mahasiswa</span></a>
          <a class="{{ request()->routeIs('search.logbooks') ? 'active' : '' }}" href="{{ route('search.logbooks') }}" title="Search Logbook"><span class="icon">📖</span> <span class="label">Search Logbook</span></a>
          <a class="{{ request()->routeIs('map.public') ? 'active' : '' }}" href="{{ route('map.public') }}" title="Peta Sebaran"><span class="icon">📍</span> <span class="label">Peta Sebaran</span></a>
        </nav>
      </div>
    @endguest

    <div class="nav-group" style="margin-top:auto">
      <nav class="menu">
        {{-- Tombol instal PWA - disembunyikan default, hanya muncul lewat JS
             saat browser menembak event `beforeinstallprompt` (lihat app.js). --}}
        <button type="button" id="pwa-install-btn" hidden title="Instal Aplikasi">
          <span class="icon">📲</span> <span class="label">Instal Aplikasi</span>
        </button>
        @auth
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar"><span class="icon">🚪</span> <span class="label">Keluar</span></button>
          </form>
        @elseif ($isGuestMode)
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar dari mode tamu"><span class="icon">🚪</span> <span class="label">Keluar Mode Tamu</span></button>
          </form>
        @else
          <a class="{{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}" title="Masuk"><span class="icon">🔐</span> <span class="label">Masuk</span></a>
        @endauth
      </nav>
    </div>
  </div>
  </aside>

  <section class="content">
    <div class="topbar">
      <div class="topbar-left">
        <button type="button" class="topbar-menu-btn" id="drawer-toggle" aria-label="Buka menu">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div style="min-width:0">
          <h2>@yield('title')</h2>
          <p>@yield('description')</p>
        </div>
      </div>
      <div class="top-actions">
        @hasrole('mahasiswa')
          <a class="btn btn-soft" href="{{ route('logbooks.create') }}">+ Input Logbook</a>
        @else
          @guest
            <a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
          @endguest
        @endhasrole
      </div>
    </div>
    <main class="container">
      <div id="offline-banner" class="alert alert-error" hidden>
        📡 Anda sedang offline. Presensi dan logbook belum bisa disimpan sampai koneksi kembali.
      </div>
      @if (session('flash_success'))
        <div class="alert alert-success">{{ session('flash_success') }}</div>
      @endif
      @if (session('flash_error'))
        <div class="alert alert-error">{{ session('flash_error') }}</div>
      @endif

      @yield('content')
    </main>
  </section>
</div>
@livewireScripts
@yield('scripts')
</body>
</html>
