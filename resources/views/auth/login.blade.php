<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Background Image */
            body {
                background-image: url('{{ asset("images/background.jpg") }}'); /* Sesuaikan nama file background */
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed; /* Agar background tidak bergerak saat scroll */
            }
            
            /* Warna input field tidak gelap, sesuai default Breeze */
            /* Jika Anda ingin mengubah warna teks input agar lebih terang, Anda bisa tambahkan */
            /* .custom-input { color: #333; } */ 
        </style>
    </head>
    
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            
            {{-- Logo Sekolah --}}
            <div class="mb-4">
                <img src="{{ asset('images/logo_sekolah.png') }}" alt="Logo Sekolah" class="w-24 h-24 object-contain">
            </div>

            {{-- Form Login dengan Transparansi --}}
            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white bg-opacity-80 backdrop-filter backdrop-blur-sm shadow-xl overflow-hidden sm:rounded-lg border border-gray-200">
                
                <h2 class="text-center text-3xl font-extrabold text-gray-800 mb-6 mt-2">
                    Sistem Absensi Guru
                </h2>
                
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="username" :value="__('Username')" class="text-gray-700" />
                        <x-text-input id="username" class="block mt-1 w-full custom-input" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div class="mt-4 mb-6">
                        <x-input-label for="password" :value="__('Password')" class="text-gray-700" />
                        <x-text-input id="password" class="block mt-1 w-full custom-input" type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="block">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                            <span class="ms-2 text-sm text-gray-700">{{ __('Ingat saya') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        {{-- Tombol "Lupa password?" dihilangkan --}}
                        <x-primary-button class="ms-3 w-full justify-center py-2 text-lg">
                            {{ __('MASUK') }}
                        </x-primary-button>
                    </div>
                    
                </form>
            </div>
        </div>
    </body>
</html>