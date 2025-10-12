<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Jadwal Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-4 text-sm text-gray-600">
                        Keterangan: <span class="text-red-500">*</span> Wajib diisi.
                    </div>
                    
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.jadwal-pelajaran.update', $jadwal->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Kolom 1 -->
                            <div class="space-y-6">
                                <!-- ========================================================== -->
                                <!-- == REVISI DI SINI: Dropdown sekarang memilih nilai lama == -->
                                <!-- ========================================================== -->
                                <div>
                                    <x-input-label for="user_id">
                                        Guru Pengajar <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <select id="user_id" name="user_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="" disabled>-- Pilih Guru --</option>
                                        @foreach ($daftarGuru as $guru)
                                            <option value="{{ $guru->id }}" @if(old('user_id', $jadwal->user_id) == $guru->id) selected @endif>
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
                                        <option value="Senin" @if(old('hari', $jadwal->hari) == 'Senin') selected @endif>Senin</option>
                                        <option value="Selasa" @if(old('hari', $jadwal->hari) == 'Selasa') selected @endif>Selasa</option>
                                        <option value="Rabu" @if(old('hari', $jadwal->hari) == 'Rabu') selected @endif>Rabu</option>
                                        <option value="Kamis" @if(old('hari', $jadwal->hari) == 'Kamis') selected @endif>Kamis</option>
                                        <option value="Jumat" @if(old('hari', $jadwal->hari) == 'Jumat') selected @endif>Jumat</option>
                                        <option value="Sabtu" @if(old('hari', $jadwal->hari) == 'Sabtu') selected @endif>Sabtu</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('hari')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="jam_ke">
                                        Jam Ke <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <x-text-input id="jam_ke" class="block mt-1 w-full" type="number" name="jam_ke" :value="old('jam_ke', $jadwal->jam_ke)" required min="1" max="10" />
                                    <x-input-error :messages="$errors->get('jam_ke')" class="mt-2" />
                                </div>
                            </div>
                            
                            <!-- Kolom 2 -->
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="kelas">
                                        Kelas <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <x-text-input id="kelas" class="block mt-1 w-full" type="text" name="kelas" :value="old('kelas', $jadwal->kelas)" required placeholder="Contoh: X RPL 1" />
                                    <x-input-error :messages="$errors->get('kelas')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="mata_pelajaran" :value="__('Mata Pelajaran (Opsional)')" />
                                    <x-text-input id="mata_pelajaran" class="block mt-1 w-full" type="text" name="mata_pelajaran" :value="old('mata_pelajaran', $jadwal->mata_pelajaran)" placeholder="Contoh: Matematika" />
                                    <x-input-error :messages="$errors->get('mata_pelajaran')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="tipe_blok">
                                        Tipe Blok <span class="text-red-500">*</span>
                                    </x-input-label>
                                    <select id="tipe_blok" name="tipe_blok" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="Setiap Minggu" @if(old('tipe_blok', $jadwal->tipe_blok) == 'Setiap Minggu') selected @endif>Setiap Minggu</option>
                                        <option value="Hanya Minggu 1" @if(old('tipe_blok', $jadwal->tipe_blok) == 'Hanya Minggu 1') selected @endif>Hanya Minggu 1</option>
                                        <option value="Hanya Minggu 2" @if(old('tipe_blok', $jadwal->tipe_blok) == 'Hanya Minggu 2') selected @endif>Hanya Minggu 2</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('tipe_blok')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
                            <x-primary-button>{{ __('Update Jadwal') }}</x-primary-button>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

