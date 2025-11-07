<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Hari Kerja') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.hari-kerja.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6 text-gray-900">
                        @if (session('success'))
                            <x-notification type="success" :message="session('success')" />
                        @endif

                        <p class="mb-6 text-gray-600">Pilih hari apa saja yang dianggap sebagai hari kerja. Pengaturan ini akan memengaruhi Dasbor Guru dan semua Laporan.</p>

                        <div class="space-y-4">
                            @foreach ($hariKerja as $hari)
                                <label for="hari_{{ $hari->id }}" class="flex items-center p-4 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" 
                                           id="hari_{{ $hari->id }}" 
                                           name="hari_aktif[]" 
                                           value="{{ $hari->nama_hari }}" 
                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                           {{ $hari->is_aktif ? 'checked' : '' }}>
                                    <span class="ms-3 font-medium text-lg text-gray-700">{{ $hari->nama_hari }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-4 mt-8">
                            <x-primary-button>{{ __('Simpan Perubahan') }}</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>