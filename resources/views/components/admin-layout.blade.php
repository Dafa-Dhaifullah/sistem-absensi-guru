<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

     {{ $headerScripts ?? '' }}
    
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
    // mini (ikon saja) untuk desktop
    sidebarMini: JSON.parse(localStorage.getItem('sidebarMini') ?? 'false'),
    // overlay mobile
    sidebarOpenMobile: false,

    init() {
      this.$watch('sidebarMini', v => localStorage.setItem('sidebarMini', JSON.stringify(v)));
    },

    toggleMini(){ this.sidebarMini = !this.sidebarMini },
    closeMobile(){ this.sidebarOpenMobile = false },
  }"
  x-on:keydown.escape.window="closeMobile()"
  class="min-h-screen"
>

    <!-- TOPBAR -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-2">
                    <!-- Mobile: toggle sidebar -->
                    <button
                        @click="sidebarOpenMobile = true"
                        class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition"
                        aria-label="Open sidebar"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Brand (topbar) -->
                    <a href="{{ route('admin.dashboard') }}" class="hidden sm:flex items-center gap-3">
                       
                        <span class="text-gray-800 font-semibold"></span>
                    </a>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                <div>{{ Auth::user()->name }}</div>
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

    <!-- SIDEBAR (Desktop) -->
    <aside
        class="hidden sm:flex fixed top-0 left-0 h-screen z-40 bg-gray-800 text-white transition-all duration-300 ease-in-out overflow-y-auto"
        :class="sidebarMini ? 'w-16' : 'w-64'"
        aria-label="Sidebar"
    >
        <div class="flex flex-col w-full">
            <!-- HEADER SIDEBAR -->
            <div class="flex items-center justify-between px-3 py-4 border-b border-gray-700">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo Sekolah" class="h-9 w-9 rounded-md object-contain"/>
                    <div class="truncate" x-show="!sidebarMini" x-transition>
                        <div class="text-lg font-semibold leading-tight">SAG</div>
                        <div class="text-xs text-gray-300 leading-tight">Sistem Absensi Guru</div>
                    </div>
                </div>
                <!-- Toggle mini -->
                <button @click="toggleMini()"
                        class="p-2 rounded-md hover:bg-gray-700 focus:outline-none"
                        :title="sidebarMini ? 'Perlebar Sidebar' : 'Ciutkan Sidebar'">
                    <svg x-show="!sidebarMini" x-transition class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <svg x-show="sidebarMini" x-transition class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- BODY MENU -->
            <div class="flex-1 px-2 py-3">
                <ul class="space-y-1 text-sm">
    {{-- ======= Menu yang bisa dilihat semua role ======= --}}
    <li>
        @php
            $dashboardRoute = 'dashboard';
            if (Auth::user()->role == 'admin') $dashboardRoute = 'admin.dashboard';
            elseif (Auth::user()->role == 'kepala_sekolah') $dashboardRoute = 'kepala-sekolah.dashboard';
        @endphp
        <a href="{{ route($dashboardRoute) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs($dashboardRoute) ? 'sidebar-active' : '' }}"> 
            {{-- (BARU) Ikon untuk Dashboard --}}
            <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span x-show="!sidebarMini" x-transition>Dashboard</span>
        </a>
    </li>

    {{-- ======= HANYA ADMIN ======= --}}
    @if(Auth::user()->role == 'admin')
        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Data Pengguna</li>

        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'admin' ? 'sidebar-active' : '' }}" title="Data Admin">
                {{-- (BARU) Ikon untuk Admin (Shield/Keamanan) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Admin</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'kepala_sekolah' ? 'sidebar-active' : '' }}" title="Data Kepala Sekolah">
                {{-- (BARU) Ikon untuk Kepala Sekolah (Briefcase/Jabatan) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.075c0 1.313-.964 2.505-2.287 2.697H5.287c-1.323-.192-2.287-1.384-2.287-2.697v-4.075M12 12.25c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Kepala Sekolah</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'piket' ? 'sidebar-active' : '' }}" title="Data Guru Piket">
                {{-- (BARU) Ikon untuk Piket (Clipboard/Tugas) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Guru Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'guru' ? 'sidebar-active' : '' }}" title="Data Guru Umum">
                {{-- (BARU) Ikon untuk Guru (Grup) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962c.513-.513.996-1.027 1.485-1.544a4.5 4.5 0 016.364 0c.489.517.972 1.031 1.485 1.544M12 12.75a4.5 4.5 0 110-9 4.5 4.5 0 010 9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Guru Umum</span>
            </a>
        </li>

        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Sistem Inti</li>

        <li>
            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-pelajaran.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Pelajaran ">
                {{-- (BARU) Ikon untuk Jadwal (Kalender) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M9 12.75h6" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Pelajaran</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.jadwal-piket.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-piket.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Piket">
                {{-- (BARU) Ikon untuk Jadwal Piket --}}
                 <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.kalender-blok.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.kalender-blok.*') ? 'sidebar-active' : '' }}" title="Manajemen Kalender Blok">
                {{-- (BARU) Ikon untuk Kalender Blok --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Kalender Blok</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.hari-libur.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.hari-libur.*') ? 'sidebar-active' : '' }}" title="Manajemen Hari Libur">
                {{-- (BARU) Ikon untuk Hari Libur --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.324h5.383c.493 0 .706.656.34.978l-4.36 3.192a.563.563 0 00-.182.635l1.658 5.131a.563.563 0 01-.84.609l-4.38-3.192a.563.563 0 00-.664 0l-4.38 3.192a.563.563 0 01-.84-.609l1.658-5.131a.563.563 0 00-.182-.635l-4.36-3.192a.563.563 0 01.34-.978h5.383a.563.563 0 00.475-.324L11.48 3.5z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Hari Libur</span>
            </a>
        </li>
    @endif

    {{-- ======= ADMIN & KEPALA SEKOLAH ======= --}}
    @if(in_array(Auth::user()->role, ['admin', 'kepala_sekolah']))
        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Laporan</li>

        <li>
            <a href="{{ route('admin.laporan.terlambat.harian') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.terlambat.harian') ? 'sidebar-active' : '' }}">
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Laporan Terlambat</span>
            </a>
        </li>
            
        <li>
            <a href="{{ route('admin.laporan.override_log') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.override_log') ? 'sidebar-active' : '' }}">
                {{-- (BARU) Ikon untuk Log Aktivitas --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Log Aktivitas Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.realtime') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('admin.laporan.realtime') ? 'sidebar-active' : 'hover:bg-gray-700' }}">
                {{-- (BARU) Ikon untuk Real-time --}}
                <svg class="w-5 h-5 {{ request()->routeIs('admin.laporan.realtime') ? 'text-white' : 'text-gray-300 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h9.75a2.25 2.25 0 012.25 2.25z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Jadwal Real-time</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.bulanan') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.bulanan') ? 'sidebar-active' : '' }}" title="Rekap Bulanan">
                {{-- (BARU) Ikon untuk Rekap --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1.125-1.5M12 16.5v4.5m-3-4.5v4.5m7.5-4.5v4.5m-7.5 0h7.5" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Rekap Bulanan</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.mingguan') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.mingguan') ? 'sidebar-active' : '' }}" title="Rekap Mingguan">
                {{-- (BARU) Ikon untuk Rekap --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15zM21 21l-5.197-5.197" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Rekap Mingguan</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.individu') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.individu') ? 'sidebar-active' : '' }}" title="Laporan Individu">
                {{-- (BARU) Ikon untuk Laporan Individu --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Laporan Individu</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.arsip') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.arsip') ? 'sidebar-active' : '' }}" title="Arsip Logbook">
                {{-- (BARU) Ikon untuk Arsip --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Arsip Logbook</span>
            </a>
        </li>
    @endif
</ul>
            </div>
        </div>
    </aside>

    <!-- SIDEBAR (Mobile overlay) -->
    <div
        class="sm:hidden"
        x-show="sidebarOpenMobile"
        x-transition.opacity
        style="display: none;"
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 z-40" @click="closeMobile()"></div>

        <!-- Panel -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 text-white shadow-xl flex flex-col overflow-y-auto"
               x-show="sidebarOpenMobile"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               style="display: none;"
        >
            <!-- Header mobile -->
            <div class="flex items-center justify-between px-3 py-4 border-b border-gray-700">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-sekolah.png') }}" class="h-9 w-9 rounded-md object-contain" alt="Logo"/>
                    <div>
                        <div class="text-lg font-semibold leading-tight">SAG</div>
                        <div class="text-xs text-gray-300 leading-tight">Sistem Absensi Guru</div>
                    </div>
                </div>
                <button @click="closeMobile()" class="p-2 rounded-md hover:bg-gray-700" aria-label="Close sidebar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body menu (re-use desktop markup  jika mau). Untuk ringkas, kita render konten yang sama via slot berikut: -->
            <div class="flex-1 px-2 py-3">
                <ul class="space-y-1 text-sm">
    {{-- ======= Menu yang bisa dilihat semua role ======= --}}
    <li>
        @php
            $dashboardRoute = 'dashboard';
            if (Auth::user()->role == 'admin') $dashboardRoute = 'admin.dashboard';
            elseif (Auth::user()->role == 'kepala_sekolah') $dashboardRoute = 'kepala-sekolah.dashboard';
        @endphp
        <a href="{{ route($dashboardRoute) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs($dashboardRoute) ? 'sidebar-active' : '' }}"> 
            {{-- (BARU) Ikon untuk Dashboard --}}
            <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </svg>
            <span x-show="!sidebarMini" x-transition>Dashboard</span>
        </a>
    </li>

    {{-- ======= HANYA ADMIN ======= --}}
    @if(Auth::user()->role == 'admin')
        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Data Pengguna</li>

        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'admin' ? 'sidebar-active' : '' }}" title="Data Admin">
                {{-- (BARU) Ikon untuk Admin (Shield/Keamanan) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Admin</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'kepala_sekolah' ? 'sidebar-active' : '' }}" title="Data Kepala Sekolah">
                {{-- (BARU) Ikon untuk Kepala Sekolah (Briefcase/Jabatan) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.075c0 1.313-.964 2.505-2.287 2.697H5.287c-1.323-.192-2.287-1.384-2.287-2.697v-4.075M12 12.25c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Kepala Sekolah</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'piket' ? 'sidebar-active' : '' }}" title="Data Guru Piket">
                {{-- (BARU) Ikon untuk Piket (Clipboard/Tugas) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Guru Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'guru' ? 'sidebar-active' : '' }}" title="Data Guru Umum">
                {{-- (BARU) Ikon untuk Guru (Grup) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962c.513-.513.996-1.027 1.485-1.544a4.5 4.5 0 016.364 0c.489.517.972 1.031 1.485 1.544M12 12.75a4.5 4.5 0 110-9 4.5 4.5 0 010 9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Data Guru Umum</span>
            </a>
        </li>

        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Sistem Inti</li>

        <li>
            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-pelajaran.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Pelajaran ">
                {{-- (BARU) Ikon untuk Jadwal (Kalender) --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M9 12.75h6" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Pelajaran</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.jadwal-piket.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-piket.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Piket">
                {{-- (BARU) Ikon untuk Jadwal Piket --}}
                 <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.kalender-blok.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.kalender-blok.*') ? 'sidebar-active' : '' }}" title="Manajemen Kalender Blok">
                {{-- (BARU) Ikon untuk Kalender Blok --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Kalender Blok</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.hari-libur.index') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.hari-libur.*') ? 'sidebar-active' : '' }}" title="Manajemen Hari Libur">
                {{-- (BARU) Ikon untuk Hari Libur --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.324h5.383c.493 0 .706.656.34.978l-4.36 3.192a.563.563 0 00-.182.635l1.658 5.131a.563.563 0 01-.84.609l-4.38-3.192a.563.563 0 00-.664 0l-4.38 3.192a.563.563 0 01-.84-.609l1.658-5.131a.563.563 0 00-.182-.635l-4.36-3.192a.563.563 0 01.34-.978h5.383a.563.563 0 00.475-.324L11.48 3.5z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Manajemen Hari Libur</span>
            </a>
        </li>
    @endif

    {{-- ======= ADMIN & KEPALA SEKOLAH ======= --}}
    @if(in_array(Auth::user()->role, ['admin', 'kepala_sekolah']))
        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Laporan</li>

        <li>
            <a href="{{ route('admin.laporan.terlambat.harian') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.terlambat.harian') ? 'sidebar-active' : '' }}">
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Laporan Terlambat</span>
            </a>
        </li>
            
        <li>
            <a href="{{ route('admin.laporan.override_log') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.override_log') ? 'sidebar-active' : '' }}">
                {{-- (BARU) Ikon untuk Log Aktivitas --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Log Aktivitas Piket</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.realtime') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 {{ request()->routeIs('admin.laporan.realtime') ? 'sidebar-active' : 'hover:bg-gray-700' }}">
                {{-- (BARU) Ikon untuk Real-time --}}
                <svg class="w-5 h-5 {{ request()->routeIs('admin.laporan.realtime') ? 'text-white' : 'text-gray-300 group-hover:text-white' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h9.75a2.25 2.25 0 012.25 2.25z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Jadwal Real-time</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.bulanan') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.bulanan') ? 'sidebar-active' : '' }}" title="Rekap Bulanan">
                {{-- (BARU) Ikon untuk Rekap --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1.125-1.5M12 16.5v4.5m-3-4.5v4.5m7.5-4.5v4.5m-7.5 0h7.5" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Rekap Bulanan</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.mingguan') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.mingguan') ? 'sidebar-active' : '' }}" title="Rekap Mingguan">
                {{-- (BARU) Ikon untuk Rekap --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15zM21 21l-5.197-5.197" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Rekap Mingguan</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.individu') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.individu') ? 'sidebar-active' : '' }}" title="Laporan Individu">
                {{-- (BARU) Ikon untuk Laporan Individu --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Laporan Individu</span>
            </a>
        </li>
        <li>
            <a href="{{ route('admin.laporan.arsip') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.arsip') ? 'sidebar-active' : '' }}" title="Arsip Logbook">
                {{-- (BARU) Ikon untuk Arsip --}}
                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span x-show="!sidebarMini" x-transition>Arsip Logbook</span>
            </a>
        </li>
    @endif
</ul>
            </div>
            <!-- PROFIL PENGGUNA (MOBILE) -->
            <div class="border-t border-gray-700 px-4 py-3">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <img class="h-8 w-8 rounded-full object-cover"
                             src="{{ Auth::user()->profile_photo_url ?? asset('images/default-avatar.png') }}"
                             alt="{{ Auth::user()->name }}">
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-white">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-400">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <a href="{{ route('profile.edit') }}"
                       class="block px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                        Profil Saya
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    </div>

    <!-- KONTEN -->
    <div :class="sidebarMini ? 'sm:ml-16' : 'sm:ml-64'">
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
