{{-- resources/views/components/admin-layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'Admin Panel' }}</title>
  <link rel="icon" href="{{ asset('images/logo-sekolah.png') }}" type="image/png">

  {{-- SET ATTR ROOT SEBELUM CSS (anti-kedip) --}}
  <script>
    (function() {
      try {
        var mini = JSON.parse(localStorage.getItem('sidebarMini') ?? 'false');
        document.documentElement.setAttribute('data-sidebar-mini', mini ? '1' : '0');
      } catch (e) {
        document.documentElement.setAttribute('data-sidebar-mini','0');
      }
    })();
  </script>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  {{ $headerScripts ?? '' }}

  <style>
    /* Hindari flash komponen Alpine yang cloaked */
    [x-cloak] { display: none !important; }

    /* Nonaktifkan transisi global di first paint */
    .preload *, .preload *::before, .preload *::after {
      transition: none !important;
      animation: none !important;
    }

    /* Lebar sidebar desktop ditentukan di root attribute */
    aside.sidebar--desktop { width: 16rem; }          /* w-64 */
    html[data-sidebar-mini="1"] aside.sidebar--desktop { width: 4rem; } /* w-16 */

    /* Desktop: label/horizontal rule/section title tersembunyi saat mini */
    html[data-sidebar-mini="1"] aside.sidebar--desktop .sidebar__label   { display: none; }
    html[data-sidebar-mini="1"] aside.sidebar--desktop .sidebar__hr      { display: none; }
    html[data-sidebar-mini="1"] aside.sidebar--desktop .sidebar__section { display: none; }

    /* Mobile overlay: label SELALU tampil */
    aside.sidebar--mobile .sidebar__label,
    aside.sidebar--mobile .sidebar__hr,
    aside.sidebar--mobile .sidebar__section { display: inline-block; }

    /* Hindari transisi width pada desktop agar tidak terlihat collapse-expand */
    aside.sidebar--desktop { transition: none !important; }

    /* Konten geser sesuai mini/non-mini (tanpa transisi) */
    .content-wrap { margin-left: 16rem; }     /* w-64 */
    @media (min-width: 640px) {
      html[data-sidebar-mini="1"] .content-wrap { margin-left: 4rem; } /* w-16 */
    }
    @media (max-width: 639.98px) {
      .content-wrap { margin-left: 0; }
    }
  </style>
  <script>
    // Matikan transisi saat initial paint
    document.documentElement.classList.add('preload');
    window.addEventListener('load', () => {
      document.documentElement.classList.remove('preload');
    });
  </script>
</head>
<body class="font-sans antialiased bg-gray-100">

  @if(session('success'))
    <x-notification type="success" :message="session('success')" />
  @endif
  @if(session('error'))
    <x-notification type="error" :message="session('error')" />
  @endif
  @if($errors->any())
    <x-notification type="error" :message="$errors->first()" />
  @endif

  <div
    x-data="{
      // HANYA untuk mobile overlay & tombol toggle mini
      sidebarMini: JSON.parse(localStorage.getItem('sidebarMini') ?? 'false'),
      sidebarOpenMobile: false,
      setRootMini(v){
        this.sidebarMini = v;
        localStorage.setItem('sidebarMini', JSON.stringify(v));
        document.documentElement.setAttribute('data-sidebar-mini', v ? '1' : '0');
      },
      toggleMini(){ this.setRootMini(!this.sidebarMini) },
      closeMobile(){ this.sidebarOpenMobile = false },
    }"
    x-on:keydown.escape.window="closeMobile()"
    class="min-h-screen"
  >
    {{-- TOPBAR --}}
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center gap-2">
            {{-- Mobile: toggle sidebar --}}
            <button
              @click="sidebarOpenMobile = true"
              class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none"
              aria-label="Open sidebar"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>

            {{-- Brand --}}
            <a href="{{ route('admin.dashboard') }}" class="hidden sm:flex items-center gap-3">
              <span class="text-gray-800 font-semibold">{{ $brand ?? '' }}</span>
            </a>
          </div>

          <div class="hidden sm:flex sm:items-center sm:ms-6">
            <x-dropdown align="right" width="48">
              <x-slot name="trigger">
                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none">
                  <div>{{ Auth::user()->name ?? 'User' }}</div>
                  <div class="ms-1">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </button>
              </x-slot>
              <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">{{ __('Profil') }}</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                  </x-dropdown-link>
                </form>
              </x-slot>
            </x-dropdown>
          </div>
        </div>
      </div>
    </nav>

    {{-- SIDEBAR DESKTOP (tanpa x-show / transisi) --}}
    <aside
      class="hidden sm:flex fixed top-0 left-0 h-screen z-40 bg-gray-800 text-white overflow-y-auto sidebar--desktop"
      aria-label="Sidebar"
    >
      <div class="flex flex-col w-full">
        <div class="flex items-center justify-between px-3 py-4 border-b border-gray-700">
          <div class="flex items-center gap-3 min-w-0">
            <img src="{{ asset('images/logo-sekolah.png') }}" class="h-9 w-9 rounded-md object-contain" alt="Logo Sekolah"/>
            <div class="truncate sidebar__label">
              <div class="text-lg font-semibold leading-tight">PRESGO</div>
              <div class="text-xs text-gray-300 leading-tight">Presensi Guru Online</div>
            </div>
          </div>
          <button @click="toggleMini()" class="p-2 rounded-md hover:bg-gray-700 focus:outline-none" :title="sidebarMini ? 'Perlebar Sidebar' : 'Ciutkan Sidebar'">
            <svg x-show="!sidebarMini" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <svg x-show="sidebarMini" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <div class="flex-1 px-2 py-3">
          @include('partials.sidebar-menu', ['context' => 'desktop'])
        </div>
      </div>
    </aside>

    {{-- SIDEBAR MOBILE (overlay, boleh transisi karena overlay) --}}
    <div class="sm:hidden" x-show="sidebarOpenMobile" x-transition.opacity style="display:none" x-cloak>
      <div class="fixed inset-0 bg-black/50 z-40" @click="closeMobile()"></div>

      <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 text-white shadow-xl flex flex-col overflow-y-auto sidebar--mobile"
             x-show="sidebarOpenMobile"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             style="display:none" x-cloak>
        <div class="flex items-center justify-between px-3 py-4 border-b border-gray-700">
          <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo-sekolah.png') }}" class="h-9 w-9 rounded-md object-contain" alt="Logo"/>
            <div>
              <div class="text-lg font-semibold leading-tight">PRESGO</div>
              <div class="text-xs text-gray-300 leading-tight">Presensi Guru Online</div>
            </div>
          </div>
          <button @click="closeMobile()" class="p-2 rounded-md hover:bg-gray-700" aria-label="Close sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="flex-1 px-2 py-3">
          @include('partials.sidebar-menu', ['context' => 'mobile'])
        </div>

        <div class="border-t border-gray-700 px-4 py-3">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <img class="h-8 w-8 rounded-full object-cover"
                   src="{{ Auth::user()->profile_photo_url ?? asset('images/default-avatar.png') }}"
                   alt="{{ Auth::user()->name ?? 'User' }}">
            </div>
            <div class="ml-3">
              <div class="text-sm font-medium text-white">{{ Auth::user()->name ?? '' }}</div>
              <div class="text-xs text-gray-400">{{ Auth::user()->email ?? '' }}</div>
            </div>
          </div>

          <div class="mt-3 space-y-1">
            <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-700 hover:text-white">Profil Saya</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-700 hover:text-white">Keluar</button>
            </form>
          </div>
        </div>
      </aside>
    </div>

    {{-- KONTEN --}}
    <div class="content-wrap">
      <main>
        @if (isset($header))
          <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
              {{ $header }}
            </div>
          </header>
        @endif

        {{ $slot }}
      </main>
    </div>
  </div>
</body>
</html>
