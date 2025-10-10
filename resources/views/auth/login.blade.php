<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                background-image: url('{{ asset("images/background.jpg") }}');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
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
                        <div class="relative">
                            <x-text-input id="password" class="block mt-1 w-full pe-10" type="password" name="password" required autocomplete="current-password" />
                            
                            <!-- ============================================== -->
                            <!-- == TOMBOL DENGAN IKON BARU == -->
                            <!-- ============================================== -->
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700">
                                <!-- Ikon Mata Terbuka (default) -->
                                <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a.75.75 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                                </svg>
                                <!-- Ikon Mata Tertutup (disembunyikan) -->
                                <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 hidden">
                                    <path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 001.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.44A11.217 11.217 0 0112 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z" />
                                    <path d="M15.75 12c0 .18-.013.357-.037.53l-2.201-2.202a5.25 5.25 0 00-6.71-6.71L4.44 7.758A11.217 11.217 0 0012 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-1.135 0-2.234-.138-3.286-.404l-2.023-2.023A5.25 5.25 0 0015.75 12z" />
                                </svg>
                            </button>
                        </div>
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

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const togglePassword = document.getElementById('togglePassword');
                const password = document.getElementById('password');
                const eyeOpen = document.getElementById('eye-open');
                const eyeClosed = document.getElementById('eye-closed');

                togglePassword.addEventListener('click', function (e) {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);

                    eyeOpen.classList.toggle('hidden');
                    eyeClosed.classList.toggle('hidden');
                });
            });
        </script>
    </body>
</html>
