<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Admin Panel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
    </head>
    <body class="font-sans antialiased bg-gray-100">
        
        <div x-data="{ sidebarOpen: window.innerWidth >= 640 }" @resize.window="sidebarOpen = window.innerWidth >= 640" class="min-h-screen">

            <!-- Navigasi Atas (Topbar) -->
            <nav class="bg-white border-b border-gray-100 sticky top-0 z-30">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            
                            <button @click="sidebarOpen = !sidebarOpen" 
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="shrink-0 flex items-center ml-4">
                                <a href="{{ route('admin.dashboard') }}">
                                    <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                                </a>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ Auth::user()->name }}</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
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

            <!-- Sidebar (Menu Samping) -->
            <aside 
                class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300 ease-in-out bg-gray-800" 
                :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
                @click.away="if (window.innerWidth < 640) sidebarOpen = false"
            >
                <div class="h-full px-3 py-4 overflow-y-auto">
                    <div class="flex items-center justify-between ps-2.5 mb-5 mt-16">
                        <a href="{{ route('admin.dashboard') }}">
                            <span class="self-center text-xl font-semibold whitespace-nowrap text-white">Sistem Absensi</span>
                        </a>
                        <button @click="sidebarOpen = false" class="sm:hidden p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <ul class="space-y-2 font-medium">
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21"><path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/><path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/></svg>
                                <span class="ms-3">Dashboard</span>
                            </a>
                        </li>
                        <hr class="border-gray-600 my-2">
                        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Data Pengguna</li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/></svg>
                                <span class="ms-3">Data Admin</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 14 18"><path d="M7 9a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm2 1H5a5.006 5.006 0 0 0-5 5v2a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2a5.006 5.006 0 0 0-5-5Z"/></svg>
                                <span class="ms-3">Data Kepala Sekolah</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span class="ms-3">Data Guru Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18"><path d="M14 2a3.963 3.963 0 0 0-1.4.267 6.439 6.439 0 0 1-1.331 6.638A4 4 0 1 0 14 2Zm1 9h-1.264A6.957 6.957 0 0 1 15 15v2a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1v-2a6.957 6.957 0 0 1 1.264-4H0A1 1 0 0 1 0 9v-1a1 1 0 0 1 1-1h1.264A6.957 6.957 0 0 1 0 3V1a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v2a6.957 6.957 0 0 1-1.264 4H14a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1Zm-5-4q0 .309-.034.616A6.97 6.97 0 0 1 10 9.616v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 16.616v-4.768a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 9.616V6.384a.998.998 0 0 1 .184-1.616A6.97 6.97 0 0 1 10 4.384v4.768a.998.998 0 0 1-.184 1.616A6.97 6.97 0 0 1 10 11.384Z"/></svg>
                                <span class="ms-3">Data Guru Umum</span>
                            </a>
                        </li>
                        <hr class="border-gray-600 my-2">
                        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Sistem Inti</li>
                        <li>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 0H2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5.5 4.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6ZM13.929 17H7.071a.5.5 0 0 1-.5-.5 3.935 3.935 0 1 1 7.858 0 .5.5 0 0 1-.5.5Z"/></svg>
                                <span class="ms-3">Manajemen Jadwal Pelajaran</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.jadwal-piket.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/></svg>
                                <span class="ms-3">Manajemen Jadwal Piket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.kalender-blok.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V4Zm-2 13H2V7h16v10ZM6 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span class="ms-3">Manajemen Kalender Blok</span>
                            </a>
                        </li>
                        <!-- ============================================== -->
                        <!-- == TAMBAHKAN LINK HARI LIBUR DI SINI == -->
                        <!-- ============================================== -->
                        <li>
                            <a href="{{ route('admin.hari-libur.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
                                </svg>
                                <span class="ms-3">Manajemen Hari Libur</span>
                            </a>
                        </li>
                        <!-- ============================================== -->
                        <hr class="border-gray-600 my-2">
                        <li class="px-3 pb-1 text-xs font-semibold text-gray-400 uppercase">Laporan</li>
                        <li>
                            <a href="{{ route('admin.laporan.realtime') }}" class="flex items-center p-2 text-white rounded-lg bg-blue-500 hover:bg-blue-600 group">
                                <svg class="w-5 h-5 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 16"><path d="M19 0H1a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1ZM2 13V2h16v11H2Z"/><path d="M5 14.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm3 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm11 .5H1a1 1 0 0 1 0-2h18a1 1 0 0 1 0 2Z"/></svg>
                                <span class="ms-3">Jadwal Real-time</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.bulanan') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M18 16.983V18H2v-1.017C2 15.899 5.59 15 10 15s8 .899 8 1.983ZM10 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M10 0a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM10 13a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/></svg>
                                <span class="ms-3">Rekap Bulanan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.mingguan') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16ZM9 13a1 1 0 0 1-1-1V8a1 1 0 0 1 2 0v4a1 1 0 0 1-1 1Zm1-5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                                <span class="ms-3">Rekap Mingguan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.individu') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm-2 5a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm2 13a7.948 7.948 0 0 1-4.949-1.889A3.99 3.99 0 0 1 9 13h2a3.99 3.99 0 0 1 2.949 1.111A7.948 7.948 0 0 1 12 18Z"/></svg>
                                <span class="ms-3">Laporan Individu</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.laporan.arsip') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                                <svg class="w-5 h-5 text-gray-400 transition duration-75 group-hover:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M19.728 10.686c-2.38 2.256-6.153 4.315-9.728 4.315S2.38 12.942 0 10.686v8.139A1.175 1.175 0 0 0 1.175 20h17.65A1.175 1.175 0 0 0 20 18.825v-8.139Zm-17.65 0c2.38 2.256 6.153 4.315 9.728 4.315s7.348-2.059 9.728-4.315V2.175A1.175 1.175 0 0 0 18.825 1H1.175A1.175 1.175 0 0 0 0 2.175v8.511Z"/></svg>
                                <span class="ms-3">Arsip Logbook</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Backdrop/Overlay -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" 
                 class="fixed inset-0 bg-black bg-opacity-50 z-30 sm:hidden"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>

            <!-- Konten Utama Halaman -->
            <div class="sm:ml-64">
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
