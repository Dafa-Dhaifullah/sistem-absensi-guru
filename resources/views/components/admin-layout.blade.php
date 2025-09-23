<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <style>[x-cloak]{ display:none !important; }</style>

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
<div
  x-data="{ sidebarOpen: window.innerWidth >= 640 }"
  @resize.window="sidebarOpen = window.innerWidth >= 640"
  @keydown.window.escape="sidebarOpen = false"
  class="min-h-screen"
>

  <!-- NAVBAR -->
  <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center gap-2">
          <!-- Toggle button -->
          <button  @click.stop="sidebarOpen = !sidebarOpen"
  class="z-50 inline-flex items-center justify-center p-2 rounded-md text-gray-800 hover:text-gray-900 hover:bg-gray-100 focus:outline-none transition">
  <svg class="h-6 w-6 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <div class="shrink-0 ml-2">
            <a href="{{ route('admin.dashboard') }}">
              <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
            </a>
          </div>
        </div>

        <!-- Profil (desktop) -->
        <div class="hidden sm:flex sm:items-center sm:ms-6">
          <x-dropdown align="right" width="48">
            <x-slot name="trigger">
              <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 bg-white hover:text-gray-800 focus:outline-none transition">
                <div>{{ Auth::user()->name }}</div>
                <div class="ms-1">
                  <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
            </x-slot>
            <x-slot name="content">
              <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                                 onclick="event.preventDefault(); this.closest('form').submit();">
                  {{ __('Log Out') }}
                </x-dropdown-link>
              </form>
            </x-slot>
          </x-dropdown>
        </div>
      </div>
    </div>
  </nav>

  <!-- SIDEBAR -->
  <aside x-cloak
  class="fixed left-0 z-40 w-64 h-screen bg-gray-800 transition-transform duration-300 ease-in-out
         top-0 sm:top-16 transform -translate-x-full"
  :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
  @click.outside="if (sidebarOpen && window.innerWidth < 640) sidebarOpen = false"
>

    <div class="h-full px-3 overflow-y-auto pt-16 sm:pt-4">
      <div class="flex items-center justify-between ps-2.5 mb-5">
        <a href="{{ route('admin.dashboard') }}">
          <span class="text-xl font-semibold whitespace-nowrap text-white">Sistem Absensi</span>
        </a>
        <!-- Close (mobile) -->
        <button @click="sidebarOpen = false"
                class="sm:hidden p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- MENU SIDEBAR -->
      <ul class="space-y-2 font-medium"
          @click="if (window.innerWidth < 640) sidebarOpen = false">
        <li>
          <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
            <span class="ms-3">Dashboard</span>
          </a>
        </li>

        <hr class="border-gray-600 my-2">
        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Data Master</li>
        <li><a href="{{ route('admin.data-guru.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Data Guru</a></li>
        <li><a href="{{ route('admin.akun-piket.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Akun Piket</a></li>
        <li><a href="{{ route('admin.akun-admin.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Akun Admin</a></li>

        <hr class="border-gray-600 my-2">
        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Sistem Inti</li>
        <li><a href="{{ route('admin.jadwal-pelajaran.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Jadwal Pelajaran</a></li>
        <li><a href="{{ route('admin.jadwal-piket.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Jadwal Piket</a></li>
        <li><a href="{{ route('admin.kalender-blok.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Manajemen Kalender Blok</a></li>

        <hr class="border-gray-600 my-2">
        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Laporan</li>
        <li><a href="{{ route('admin.laporan.realtime') }}" class="flex items-center p-2 text-white rounded-lg bg-blue-500 hover:bg-blue-600">Jadwal Real-time</a></li>
        <li><a href="{{ route('admin.laporan.bulanan') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Rekap Bulanan</a></li>
        <li><a href="{{ route('admin.laporan.mingguan') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Rekap Mingguan</a></li>
        <li><a href="{{ route('admin.laporan.individu') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Laporan Individu</a></li>
        <li><a href="{{ route('admin.laporan.arsip') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700">Arsip Logbook</a></li>
      </ul>
    </div>
  </aside>

  <!-- OVERLAY (mobile) -->
  <div x-cloak
       x-show="sidebarOpen"
       @click="sidebarOpen = false"
       class="fixed inset-0 bg-black bg-opacity-50 z-40 sm:hidden"
       x-transition:enter="transition-opacity ease-linear duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity ease-linear duration-300"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
  </div>

  <!-- KONTEN -->
  <div class="transition-all duration-300 ease-in-out pt-16"
       :class="{ 'sm:ml-64': sidebarOpen, 'sm:ml-0': !sidebarOpen }">
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
