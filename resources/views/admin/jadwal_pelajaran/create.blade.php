<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Jadwal Pelajaran Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-4 text-sm text-gray-600">
                        Keterangan: <span class="text-red-500">*</span> Wajib diisi.
                    </div>
                    

                    
                    <form action="{{ route('admin.jadwal-pelajaran.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="user_id">
                                        Guru Pengajar <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <select id="user_id" name="user_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="" disabled selected>-- Pilih Guru --</option>
                                        @foreach ($daftarGuru as $guru)
                                            <option value="{{ $guru->id }}" {{ old('user_id') == $guru->id ? 'selected' : '' }}>
                                                {{ $guru->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="hari">
                                        Hari <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <select id="hari" name="hari" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="" disabled selected>-- Pilih Hari --</option>
                                        <option value="Senin" {{ old('hari') == 'Senin' ? 'selected' : '' }}>Senin</option>
                                        <option value="Selasa" {{ old('hari') == 'Selasa' ? 'selected' : '' }}>Selasa</option>
                                        <option value="Rabu" {{ old('hari') == 'Rabu' ? 'selected' : '' }}>Rabu</option>
                                        <option value="Kamis" {{ old('hari') == 'Kamis' ? 'selected' : '' }}>Kamis</option>
                                        <option value="Jumat" {{ old('hari') == 'Jumat' ? 'selected' : '' }}>Jumat</option>
                                        <option value="Sabtu" {{ old('hari') == 'Sabtu' ? 'selected' : '' }}>Sabtu</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('hari')" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="kelas">
                                        Kelas <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <x-text-input id="kelas" class="block mt-1 w-full" type="text" name="kelas" :value="old('kelas')" required placeholder="Contoh: X RPL 1" />
                                    <x-input-error :messages="$errors->get('kelas')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="mata_pelajaran" :value="__('Mata Pelajaran (Opsional)')" />
                                    <x-text-input id="mata_pelajaran" class="block mt-1 w-full" type="text" name="mata_pelajaran" :value="old('mata_pelajaran')" placeholder="Contoh: Matematika" />
                                    <x-input-error :messages="$errors->get('mata_pelajaran')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="tipe_blok">
                                        Tipe Blok <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <select id="tipe_blok" name="tipe_blok" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="Setiap Minggu" {{ old('tipe_blok') == 'Setiap Minggu' ? 'selected' : '' }}>Setiap Minggu</option>
                                        <option value="Hanya Minggu 1" {{ old('tipe_blok') == 'Hanya Minggu 1' ? 'selected' : '' }}>Hanya Minggu 1</option>
                                        <option value="Hanya Minggu 2" {{ old('tipe_blok') == 'Hanya Minggu 2' ? 'selected' : '' }}>Hanya Minggu 2</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('tipe_blok')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t pt-6">
                            <x-input-label>
                                Pilih Jam Mengajar <span class="text-red-500">*</span>
                            </x-input-label>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-2 border p-4 rounded-md">
                                @for ($i = 1; $i <= 10; $i++)
                                <label for="jam_{{ $i }}" class="flex items-center">
                                    <input id="jam_{{ $i }}" type="checkbox" name="jam_ke[]" value="{{ $i }}" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ms-2 text-sm text-gray-600">{{ __('Jam ke-') }}{{ $i }}</span>
                                </label>
                                @endfor
                            </div>
                            <x-input-error :messages="$errors->get('jam_ke')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
                            <x-primary-button>{{ __('Simpan Jadwal') }}</x-primary-button>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>