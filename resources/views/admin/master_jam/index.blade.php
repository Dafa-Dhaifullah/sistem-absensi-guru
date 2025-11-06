<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Jam Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <x-notification type="success" :message="session('success')" />
                    @endif

                    <p class="mb-6 text-gray-600">Pilih hari untuk mengatur rentang waktu jam pelajaran. Pastikan jam tidak tumpang tindih.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($daftarHari as $hari)
                            <a href="{{ route('admin.master-jam.edit', $hari) }}" class="block p-6 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all duration-150 shadow-sm">
                                <div class="font-semibold text-lg text-gray-800">Hari {{ $hari }}</div>
                                <p class="text-sm text-gray-600">Klik untuk mengedit jam pelajaran</p>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>