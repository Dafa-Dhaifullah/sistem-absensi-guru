<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Akun Piket Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.akun-piket.store') }}" method="POST"> @csrf
                        <div class="space-y-6">
                            
                            <div>
                                <x-input-label for="name" :value="__('Nama')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="mt-4">
        <x-input-label for="username" :value="__('Username (untuk login)')" />
        <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autocomplete="username" />
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

                            <div class="mt-4">
                                <x-input-label for="email" :value="__('Email (opsional)')" />
                                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="password_confirmation" :value="__('Konfirmasi Password')" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="mt-4">
    <x-input-label for="role" :value="__('Hak Akses (Role)')" />
    <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
        <option value="" disabled selected>-- Pilih Hak Akses --</option>
        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (TU)</option>
        <option value="kepala_sekolah" {{ old('role') == 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
        <option value="piket" {{ old('role') == 'piket' ? 'selected' : '' }}>Guru Piket</option>
        <option value="guru" {{ old('role') == 'guru' ? 'selected' : '' }}>Guru Umum</option>
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>



                            <div class="flex items-center gap-4 mt-6">
                                <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                                <a href="{{ route('admin.akun-piket.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a> </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>