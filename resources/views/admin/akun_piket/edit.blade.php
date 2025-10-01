<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Akun Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.akun-piket.update', $user->id) }}" method="POST"> @csrf
                        @method('PUT')
                        <div class="space-y-6">
                            
                            <div>
                                <x-input-label for="name" :value="__('Nama ')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="mt-4">
        <x-input-label for="username" :value="__('Username (untuk login)')" />
        <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autocomplete="username" />
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

                            <div class="mt-4">
                                <x-input-label for="email" :value="__('Email (opsional)')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)"  />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="password" :value="__('Password Baru (Opsional)')" />
                                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="mt-4">
    <x-input-label for="role" :value="__('Hak Akses (Role)')" />
    <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        <option value="admin" @if(old('role', $user->role) == 'admin') selected @endif>Admin (TU)</option>
        <option value="kepala_sekolah" @if(old('role', $user->role) == 'kepala_sekolah') selected @endif>Kepala Sekolah</option>
        <option value="piket" @if(old('role', $user->role) == 'piket') selected @endif>Guru Piket</option>
        <option value="guru" @if(old('role', $user->role) == 'guru') selected @endif>Guru Umum</option>
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>


                    

                            <div class="flex items-center gap-4 mt-6">
                                <x-primary-button>{{ __('Update') }}</x-primary-button>
                                <a href="{{ route('admin.akun-piket.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a> </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>