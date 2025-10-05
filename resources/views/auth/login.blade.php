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
            body {
                background-image: url('{{ asset("images/background.jpg") }}');
                background-size: cover;
                background-position: center;
            }
        </style>
    </head>
    
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            
            <div>
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo Sekolah" class="w-24 h-24 object-contain">
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white bg-opacity-80 backdrop-filter backdrop-blur-sm shadow-xl overflow-hidden sm:rounded-lg border border-gray-200">
                
                <h2 class="text-center text-3xl font-extrabold text-gray-800 mb-6 mt-2">
                    Sistem Absensi Guru
                </h2>
                
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div>
                        <x-input-label for="username" :value="__('Username')" class="text-gray-700" />
                        <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password" :value="__('Password')" class="text-gray-700" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="block mt-4">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                            <span class="ms-2 text-sm text-gray-700">{{ __('Ingat saya') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <x-primary-button class="w-full justify-center py-3 text-lg">
                            {{ __('MASUK') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>