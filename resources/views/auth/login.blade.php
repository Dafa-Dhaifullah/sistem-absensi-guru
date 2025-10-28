<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login</title>
        <link rel="icon" href="{{ asset('images/logo-sekolah.png') }}" type="image/png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts (Vite) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
             background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 50%, #60A5FA 100%);
  background-attachment: fixed;
  color: #fff;
  font-family: 'Inter', sans-serif;
            }
        </style>
    </head>
    
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            
            <div>
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo Sekolah" class="w-24 h-24 object-contain">
            </div>

           <div
  class="w-full sm:max-w-md mt-10 px-6 py-8
         bg-white shadow-2xl rounded-2xl
         border border-gray-200/60
         backdrop-blur-sm transition-all duration-300"
>
  <h2 class="text-center text-3xl font-extrabold text-gray-800 mb-6 mt-2">
    PRESGO
  </h2>

  <x-auth-session-status class="mb-4" :status="session('status')" />

  <form method="POST" action="{{ route('login') }}">
    @csrf

    <div>
      <x-input-label for="username" :value="__('Username')" class="text-gray-700" />
      <x-text-input
        id="username"
        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
        type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
      <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <div class="mt-4">
      <x-input-label for="password" :value="__('Password')" class="text-gray-700" />

      <div class="relative">
        <x-text-input
          id="password"
          class="block mt-1 w-full pe-10 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
          type="password" name="password" required autocomplete="current-password" />

        <!-- Toggle Password -->
        <button
          type="button"
          id="togglePassword"
          class="absolute inset-y-0 right-0 flex items-center px-3
                 text-gray-500 hover:text-gray-700 transition-colors duration-200"
          aria-label="Toggle password visibility"
        >
          <!-- Eye Open -->
          <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
               class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.036 12.322a11.962 11.962 0 0119.928 0M4.5 12a7.5 7.5 0 1115 0m-7.5 3.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"/>
          </svg>

          <!-- Eye Closed -->
          <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
               class="w-5 h-5 hidden">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.29 19.5 12 19.5c4.71 0 8.774-3.162 10.066-7.5a10.45 10.45 0 00-1.272-2.58M15 12a3 3 0 11-6 0 3 3 0 016 0zM4.5 4.5l15 15"/>
          </svg>
        </button>
      </div>

      <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div class="block mt-4">
      <label for="remember_me" class="inline-flex items-center">
        <input id="remember_me" type="checkbox"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               name="remember">
        <span class="ms-2 text-sm text-gray-700">{{ __('Ingat saya') }}</span>
      </label>
    </div>

    <div class="flex items-center justify-end mt-6">
      <x-primary-button
        class="w-full justify-center py-3 text-lg bg-indigo-600 hover:bg-indigo-700
               text-white font-semibold shadow-md">
        {{ __('MASUK') }}
      </x-primary-button>
    </div>
  </form>
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
