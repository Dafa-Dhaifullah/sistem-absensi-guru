<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Guru Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center text-gray-900">
                    <h3 class="text-2xl font-bold text-blue-600">Hari Ini Libur!</h3>
                    <p class="mt-2 text-lg text-gray-700">
                        {{ $keterangan }}
                    </p>
                    <p class="mt-4 text-gray-500">
                        Tidak ada jadwal absensi yang perlu dipantau hari ini. Selamat beristirahat.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
