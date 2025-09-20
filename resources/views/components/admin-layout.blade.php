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
    </head>
    <body class="font-sans antialiased bg-gray-100">
        
        <nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <button data-drawer-target="default-sidebar" data-drawer-toggle="default-sidebar" aria-controls="default-sidebar" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out sm:hidden">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        </nav>

        <aside id="default-sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
            <div class="h-full px-3 py-4 overflow-y-auto bg-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center ps-2.5 mb-5">
                    <span class="self-center text-xl font-semibold whitespace-nowrap text-white">Absensi Guru</span>
                </a>
                
                <ul class="space-y-2 font-medium">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Dashboard</span>
                        </a>
                    </li>
                    
                    <hr class="border-gray-600">
                    <li class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase">Data Master</li>
                    
                    <li>
                        <a href="{{ route('admin.data-guru.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Data Guru</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.akun-piket.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Akun Piket</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.akun-admin.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Akun Admin</span>
                        </a>
                    </li>

                    <hr class="border-gray-600">
                    <li class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase">Sistem Inti</li>

                    <li>
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Jadwal Pelajaran</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Jadwal Piket</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="flex items-center p-2 text-white rounded-lg hover:bg-gray-700 group">
                            <span class="ms-3">Manajemen Kalender Blok</span>
                        </a>
                    </li>

                    <hr class="border-gray-600">
                    <li class="px-3 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase">Laporan</li>
                    </ul>
            </div>
        </aside>

        <div class="sm:ml-64"> <main>
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

        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    </body>
</html>