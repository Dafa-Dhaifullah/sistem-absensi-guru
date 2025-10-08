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

     {{ $headerScripts ?? '' }}
    
</head>
<body class="font-sans antialiased bg-gray-100">

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
                        <a href="{{ route($dashboardRoute) }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs($dashboardRoute) ? 'sidebar-active' : '' }}"> 
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21"><path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/><path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/></svg>
                            <span x-show="!sidebarMini" x-transition>Dashboard</span>
                        </a>
                    </li>

                    {{-- ======= HANYA ADMIN ======= --}}
                    @if(Auth::user()->role == 'admin')
                        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
                        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Data Pengguna</li>

                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'admin' ? 'sidebar-active' : '' }}"
                               title="Data Admin">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Admin</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'kepala_sekolah' ? 'sidebar-active' : '' }}"
                               title="Data Kepala Sekolah">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 14 18"><path d="M7 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm2 1H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Kepala Sekolah</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'piket' ? 'sidebar-active' : '' }}"
                               title="Data Guru Piket">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Guru Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.pengguna.*') && request('role') == 'guru' ? 'sidebar-active' : '' }}"
                               title="Data Guru Umum">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18"><path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1v-2a6.957 6.957 0 0 1 1.264-4H0A1 1 0 0 1 0 9v-1a1 1 0 0 1 1-1h1.264A6.957 6.957 0 0 1 0 3V1a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v2a6.957 6.957 0 0 1-1.264 4H14a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1Zm-5-4q0 .309-.034.616A6.97 6.97 0 0 1 10 9.616v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 16.616v-4.768a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 11.384V6.384a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 4.384v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 11.384Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Guru Umum</span>
                            </a>
                        </li>

                        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
                        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Sistem Inti</li>

                        <li>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-pelajaran.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Pelajaran ">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 0H2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5.5 4.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6ZM13.929 17H7.071a.5.5 0 0 1-.5-.5 3.935 3.935 0 1 1 7.858 0 .5.5 0 0 1-.5.5Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Pelajaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.jadwal-piket.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.jadwal-piket.*') ? 'sidebar-active' : '' }}" title="Manajemen Jadwal Piket">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.kalender-blok.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.kalender-blok.*') ? 'sidebar-active' : '' }}" title="Manajemen Kalender Blok">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4Zm-2 13H2V7h16v10ZM6 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Kalender Blok</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.hari-libur.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.hari-libur.*') ? 'sidebar-active' : '' }}" title="Manajemen Hari Libur">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
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
        <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V9" />
        </svg>
        <span x-show="!sidebarMini" x-transition>Laporan Terlambat</span>
    </a>
</li>
                     
<li>
                                <a href="{{ route('admin.laporan.override_log') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 {{ request()->routeIs('admin.laporan.override_log') ? 'sidebar-active' : '' }}">
                                    <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                                    </svg>
                                    <span x-show="!sidebarMini" x-transition>Log Override</span></span>
                                </a>
                            </li>
                        <li>
                            <a href="{{ route('admin.laporan.realtime') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 bg-blue-500 hover:bg-blue-600 {{ request()->routeIs('admin.laporan.realtime') ? 'sidebar-active' : '' }}">
                                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16"><path d="M19 0H1a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1ZM2 13V2h16v11H2Z"/><path d="M5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm11 .5H1a1 1 0 0 1 0-2h18a1 1 0 0 1 0 2Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Jadwal Real-time</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.bulanan') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.bulanan') ? 'sidebar-active' : '' }}" title="Rekap Bulanan">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 16.983V18H2v-1.017C2 15.899 5.59 15 10 15s8 .899 8 1.983ZM10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M10 0a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM10 13a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Rekap Bulanan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.mingguan') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.mingguan') ? 'sidebar-active' : '' }}" title="Rekap Mingguan">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16ZM9 13a1 1 0 0 1-1-1V8a1 1 0 0 1 2 0v4a1 1 0 0 1-1 1Zm1-5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Rekap Mingguan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.individu') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.individu') ? 'sidebar-active' : '' }}" title="Laporan Individu">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm-2 5a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm2 13a7.948 7.948 0 0 1-4.949-1.889A3.99 3.99 0 0 1 9 13h2a3.99 3.99 0 0 1 2.949 1.111A7.948 7.948 0 0 1 12 18Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Laporan Individu</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.arsip') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.laporan.arsip') ? 'sidebar-active' : '' }}" title="Arsip Logbook">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M19.728 10.686c-2.38 2.256-6.153 4.315-9.728 4.315S2.38 12.942 0 10.686v8.139A1.175 1.175 0 0 0 1.175 20h17.65A1.175 1.175 0 0 0 20 18.825v-8.139Zm-17.65 0c2.38 2.256 6.153 4.315 9.728 4.315s7.348-2.059 9.728-4.315V2.175A1.175 1.175 0 0 0 18.825 1H1.175A1.175 1.175 0 0 0 0 2.175v8.511Z"/></svg>
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
                        <a href="{{ route($dashboardRoute) }}"
                           class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21"><path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/><path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/></svg>
                            <span x-show="!sidebarMini" x-transition>Dashboard</span>
                        </a>
                    </li>

                    {{-- ======= HANYA ADMIN ======= --}}
                    @if(Auth::user()->role == 'admin')
                        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
                        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Data Pengguna</li>

                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700"
                               title="Data Admin">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Admin</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700"
                               title="Data Kepala Sekolah">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 14 18"><path d="M7 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm2 1H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Kepala Sekolah</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700"
                               title="Data Guru Piket">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Guru Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700"
                               title="Data Guru Umum">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18"><path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1v-2a6.957 6.957 0 0 1 1.264-4H0A1 1 0 0 1 0 9v-1a1 1 0 0 1 1-1h1.264A6.957 6.957 0 0 1 0 3V1a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v2a6.957 6.957 0 0 1-1.264 4H14a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1Zm-5-4q0 .309-.034.616A6.97 6.97 0 0 1 10 9.616v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 16.616v-4.768a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 11.384V6.384a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 4.384v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 11.384Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Data Guru Umum</span>
                            </a>
                        </li>

                        <hr class="border-gray-700 my-2" x-show="!sidebarMini" x-transition>
                        <li x-show="!sidebarMini" x-transition class="px-3 pb-1 text-[11px] font-semibold text-gray-400 uppercase">Sistem Inti</li>

                        <li>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Manajemen Jadwal Pelajaran">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 0H2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5.5 4.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6ZM13.929 17H7.071a.5.5 0 0 1-.5-.5 3.935 3.935 0 1 1 7.858 0 .5.5 0 0 1-.5.5Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Pelajaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.jadwal-piket.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Manajemen Jadwal Piket">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Jadwal Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.kalender-blok.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Manajemen Kalender Blok">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4Zm-2 13H2V7h16v10ZM6 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Manajemen Kalender Blok</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.hari-libur.index') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Manajemen Hari Libur">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
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
    <a href="{{ route('admin.laporan.terlambat.harian') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700">
        <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V9" />
        </svg>
        <span x-show="!sidebarMini" x-transition>Laporan Terlambat</span>
    </a>
</li>
                       <li>
                                <a href="{{ route('admin.laporan.override_log') }}" class="group flex items-center gap-3 rounded-lg px-3 py-2 text-white hover:bg-gray-700 ">
                                    <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                                    </svg>
                                    <span x-show="!sidebarMini" x-transition>Log Override</span></span>
                                </a>
                            </li>

                        <li>
                            <a href="{{ route('admin.laporan.realtime') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 bg-blue-500 hover:bg-blue-600">
                                <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16"><path d="M19 0H1a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1ZM2 13V2h16v11H2Z"/><path d="M5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm11 .5H1a1 1 0 0 1 0-2h18a1 1 0 0 1 0 2Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Jadwal Real-time</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.bulanan') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Rekap Bulanan">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 16.983V18H2v-1.017C2 15.899 5.59 15 10 15s8 .899 8 1.983ZM10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M10 0a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM10 13a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Rekap Bulanan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.mingguan') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Rekap Mingguan">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16ZM9 13a1 1 0 0 1-1-1V8a1 1 0 0 1 2 0v4a1 1 0 0 1-1 1Zm1-5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Rekap Mingguan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.individu') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Laporan Individu">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm-2 5a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm2 13a7.948 7.948 0 0 1-4.949-1.889A3.99 3.99 0 0 1 9 13h2a3.99 3.99 0 0 1 2.949 1.111A7.948 7.948 0 0 1 12 18Z"/></svg>
                                <span x-show="!sidebarMini" x-transition>Laporan Individu</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.arsip') }}"
                               class="group flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-700" title="Arsip Logbook">
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M19.728 10.686c-2.38 2.256-6.153 4.315-9.728 4.315S2.38 12.942 0 10.686v8.139A1.175 1.175 0 0 0 1.175 20h17.65A1.175 1.175 0 0 0 20 18.825v-8.139Zm-17.65 0c2.38 2.256 6.153 4.315 9.728 4.315s7.348-2.059 9.728-4.315V2.175A1.175 1.175 0 0 0 18.825 1H1.175A1.175 1.175 0 0 0 0 2.175v8.511Z"/></svg>
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
