<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Jadwal Piket: <span class="text-blue-600">{{ $hari }} - Sesi {{ $sesi }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.jadwal-piket.update', ['hari' => $hari, 'sesi' => $sesi]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-6 text-gray-900">
                        <p class="mb-4 text-gray-600">
                            Pilih satu atau beberapa guru yang akan bertugas untuk slot ini.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 border rounded-lg p-4">
                            @forelse ($daftarGuruPiket as $guru)
                                <label for="guru_{{ $guru->id }}" 
                                       class="flex items-center p-3 rounded-lg hover:bg-gray-50 border cursor-pointer
                                       @if($selected_ids->contains($guru->id)) bg-blue-50 border-blue-300 @else bg-white @endif">
                                    
                                    <input type="checkbox" 
                                           id="guru_{{ $guru->id }}" 
                                           name="user_ids[]" 
                                           value="{{ $guru->id }}"
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           @if($selected_ids->contains($guru->id)) checked @endif>
                                           
                                    <span class="ms-3 text-sm font-medium text-gray-700">{{ $guru->name }}</span>
                                </label>
                            @empty
                                <p class="text-gray-500 col-span-full">
                                    Belum ada data guru piket di sistem. Silakan tambahkan di menu "Manajemen Akun Piket".
                                </p>
                            @endforelse
                        </div>
                        <x-input-error :messages="$errors->get('user_ids.*')" class="mt-2" />

                    </div>
                    
                    <div class="flex items-center gap-4 px-6 py-4 bg-gray-50 border-t">
                        <x-primary-button>{{ __('Simpan Perubahan') }}</x-primary-button>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>