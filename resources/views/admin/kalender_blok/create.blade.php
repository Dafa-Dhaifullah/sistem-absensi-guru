<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Rentang Waktu Blok') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.kalender-blok.store') }}" method="POST">
                        @csrf
                        <div class="space-y-6">
                            
                            <div>
                                <x-input-label for="tipe_minggu" :value="__('Tipe Minggu')" />
                                <select id="tipe_minggu" name="tipe_minggu" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled selected>Pilih Tipe</option>
                                    <option value="Minggu 1" {{ old('tipe_minggu') == 'Minggu 1' ? 'selected' : '' }}>Minggu 1</option>
                                    <option value="Minggu 2" {{ old('tipe_minggu') == 'Minggu 2' ? 'selected' : '' }}>Minggu 2</option>
                                </select>
                                <x-input-error :messages="$errors->get('tipe_minggu')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="tanggal_mulai" :value="__('Tanggal Mulai')" />
                                <x-text-input id="tanggal_mulai" class="block mt-1 w-full" type="date" name="tanggal_mulai" :value="old('tanggal_mulai')" required />
                                <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="tanggal_selesai" :value="__('Tanggal Selesai')" />
                                <x-text-input id="tanggal_selesai" class="block mt-1 w-full" type="date" name="tanggal_selesai" :value="old('tanggal_selesai')" required />
                                <x-input-error :messages="$errors->get('tanggal_selesai')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4 mt-6">
                                <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                                <a href="{{ route('admin.kalender-blok.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>