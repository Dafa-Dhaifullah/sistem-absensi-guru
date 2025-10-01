<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Data Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.pengguna.update', $pengguna->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="name" :value="__('Nama Lengkap')" />
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $pengguna->name)" required autofocus />
                                </div>
                                <div>
                                    <x-input-label for="username" :value="__('Username (untuk login)')" />
                                    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $pengguna->username)" required />
                                </div>
                                <div>
                                    <x-input-label for="role" :value="__('Hak Akses (Role)')" />
                                    <select id="role" name="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="admin" @if(old('role', $pengguna->role) == 'admin') selected @endif>Admin (TU)</option>
                                        <option value="kepala_sekolah" @if(old('role', $pengguna->role) == 'kepala_sekolah') selected @endif>Kepala Sekolah</option>
                                        <option value="piket" @if(old('role', $pengguna->role) == 'piket') selected @endif>Guru Piket</option>
                                        <option value="guru" @if(old('role', $pengguna->role) == 'guru') selected @endif>Guru Umum</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="password" :value="__('Password Baru (Kosongkan jika tidak diubah)')" />
                                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="nip" :value="__('NIP (Opsional)')" />
                                    <x-text-input id="nip" class="block mt-1 w-full" type="text" name="nip" :value="old('nip', $pengguna->nip)" />
                                </div>
                                <div>
                                    <x-input-label for="no_wa" :value="__('No. WhatsApp (Opsional)')" />
                                    <x-text-input id="no_wa" class="block mt-1 w-full" type="text" name="no_wa" :value="old('no_wa', $pengguna->no_wa)" placeholder="0812..." />
                                </div>
                                <div>
                                    <x-input-label for="email" :value="__('Email (Opsional)')" />
                                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $pengguna->email)" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation" :value="__('Konfirmasi Password Baru')" />
                                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
                            <x-primary-button>{{ __('Update') }}</x-primary-button>
                            <a href="{{ route('admin.pengguna.index') }}">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>