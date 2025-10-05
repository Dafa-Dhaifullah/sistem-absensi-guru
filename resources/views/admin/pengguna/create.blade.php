<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Pengguna Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        Keterangan: <span class="text-red-500">*</span> Wajib diisi.
                    </div>
                    
                    <form action="{{ route('admin.pengguna.store') }}" method="POST">
                        @csrf
                        
                        <!-- Input role tersembunyi, ini yang akan dikirim ke controller -->
                        <input type="hidden" name="role" value="{{ $role }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Kolom 1 -->
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="name">Nama Lengkap <span class="text-red-500">*</span></x-input-label>
                                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="username">Username (untuk login) <span class="text-red-500">*</span></x-input-label>
                                    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required />
                                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                                </div>

                                <!-- Dropdown Role (Tampil tapi Disable) -->
                                <div>
                                    <x-input-label for="role_disabled">Hak Akses (Role)</x-input-label>
                                    <select id="role_disabled" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm bg-gray-100 text-gray-500" disabled>
                                        <option value="admin" @if($role == 'admin') selected @endif>Admin</option>
                                        <option value="kepala_sekolah" @if($role == 'kepala_sekolah') selected @endif>Kepala Sekolah</option>
                                        <option value="piket" @if($role == 'piket') selected @endif>Guru Piket</option>
                                        <option value="guru" @if($role == 'guru') selected @endif>Guru Umum</option>
                                    </select>
                                    
                                </div>

                                <div>
                                    <x-input-label for="password">Password <span class="text-red-500">*</span></x-input-label>
                                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Kolom 2 -->
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="nip" :value="__('NIP (Opsional)')" />
                                    <x-text-input id="nip" class="block mt-1 w-full" type="text" name="nip" :value="old('nip')" />
                                    <x-input-error :messages="$errors->get('nip')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="no_wa" :value="__('No. WhatsApp (Opsional)')" />
                                    <x-text-input id="no_wa" class="block mt-1 w-full" type="text" name="no_wa" :value="old('no_wa')" placeholder="0812..." />
                                    <x-input-error :messages="$errors->get('no_wa')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="email" :value="__('Email (Opsional)')" />
                                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation">Konfirmasi Password <span class="text-red-500">*</span></x-input-label>
                                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
                            <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                            <a href="{{ url()->previous() }}">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
