<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', config('app.name'))</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0B3A5B">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="icon" href="/icons/icon-192.png">
  <link rel="apple-touch-icon" href="/icons/icon-192.png">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
@php
  $activeUser = auth()->user();
  $initials = $activeUser
    ? strtoupper(collect(explode(' ', trim($activeUser->name)))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''))
    : '';
  $roleLabel = $activeUser ? \Illuminate\Support\Str::headline($activeUser->getRoleNames()->first() ?? 'mahasiswa') : null;
  $subLabel = $activeUser ? ($activeUser->hasRole('mahasiswa') ? $activeUser->faculty : $activeUser->region?->name) : null;
@endphp
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">K</div>
      <div>
        <h1>Logbook KKN</h1>
        <p>KKN-PPM UGM</p>
      </div>
    </div>

    @auth
      <div class="role-card">
        <div class="avatar">{{ $initials }}</div>
        <div>
          <div class="name">{{ $activeUser->name }}</div>
          <div class="desc">{{ $roleLabel }}@if ($subLabel) &middot; {{ $subLabel }} @endif</div>
        </div>
      </div>
    @else
      <div class="role-card">
        <div class="avatar" style="background:rgba(255,255,255,.14);color:#fff">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
        </div>
        <div>
          <div class="name">Pengunjung</div>
          <div class="desc">Belum masuk</div>
        </div>
      </div>
    @endauth

    <div class="nav-group">
      <div class="nav-title">Menu Utama</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><span class="icon">🏠</span> Beranda</a>

        @auth
          <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="icon">📊</span> Dashboard</a>
          <a class="{{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}"><span class="icon">👤</span> Data KKN</a>
          @hasrole('mahasiswa')
            <a class="{{ request()->routeIs('logbooks.create') ? 'active' : '' }}" href="{{ route('logbooks.create') }}"><span class="icon">📝</span> Input Logbook</a>
            <a class="{{ request()->routeIs('logbooks.index') ? 'active' : '' }}" href="{{ route('logbooks.index') }}"><span class="icon">📚</span> Logbook Saya</a>
            <a class="{{ request()->routeIs('map.mine') ? 'active' : '' }}" href="{{ route('map.mine') }}"><span class="icon">🗺️</span> Peta Saya</a>
          @endhasrole
        @endauth
      </nav>
    </div>

    @hasrole('mahasiswa')
    <div class="nav-group">
      <div class="nav-title">Presensi</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('attendance.check-in') ? 'active' : '' }}" href="{{ route('attendance.check-in') }}"><span class="icon">✅</span> Presensi Harian</a>
        <a class="{{ request()->routeIs('assist-attendances.*') ? 'active' : '' }}" href="{{ route('assist-attendances.index') }}"><span class="icon">🤝</span> Presensi Bantuan</a>
      </nav>
    </div>
    @endhasrole

    @hasanyrole(implode('|', \App\Models\Student::SUPERVISORY_ROLES))
    <div class="nav-group">
      <div class="nav-title">Panel Supervisi</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('overview') ? 'active' : '' }}" href="{{ route('overview') }}"><span class="icon">🧭</span> Overview Wilayah</a>
        @php($unread = auth()->user()->unreadNotifications()->count())
        <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
          <span class="icon">🔔</span> Notifikasi
          @if ($unread)
            <span class="badge">{{ $unread }}</span>
          @endif
        </a>
      </nav>
    </div>
    @endhasanyrole

    @guest
      <div class="nav-group">
        <div class="nav-title">Tanpa Login</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('search.students') ? 'active' : '' }}" href="{{ route('search.students') }}"><span class="icon">🔎</span> Search Mahasiswa</a>
          <a class="{{ request()->routeIs('search.logbooks') ? 'active' : '' }}" href="{{ route('search.logbooks') }}"><span class="icon">📖</span> Search Logbook</a>
          <a class="{{ request()->routeIs('map.public') ? 'active' : '' }}" href="{{ route('map.public') }}"><span class="icon">📍</span> View Peta</a>
        </nav>
      </div>
    @endguest

    <div class="nav-group" style="margin-top:auto">
      <nav class="menu">
        @auth
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><span class="icon">🚪</span> Keluar</button>
          </form>
        @else
          <a class="{{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}"><span class="icon">🔐</span> Login SSO Google</a>
        @endauth
      </nav>
    </div>
  </aside>

  <section class="content">
    <div class="topbar">
      <div>
        <h2>@yield('title')</h2>
        <p>@yield('description')</p>
      </div>
      <div class="top-actions">
        @hasrole('mahasiswa')
          <a class="btn btn-soft" href="{{ route('logbooks.create') }}">+ Input Logbook</a>
        @else
          @guest
            <a class="btn btn-primary" href="{{ route('login') }}">Login Mahasiswa</a>
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
