<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generator QR Code Statis untuk Kelas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">
                        Pilih kelas di bawah ini untuk membuat dan mencetak QR Code absensi. Pastikan nama kelas sudah benar di Manajemen Jadwal Pelajaran.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @forelse ($daftarKelas as $item)
                            <div class="p-4 border rounded-lg flex items-center justify-between">
                                <span class="font-semibold">{{ $item->kelas }}</span>
                                <a href="{{ route('admin.qrcode.generator.print', ['kelas' => $item->kelas]) }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                    Buat QR
                                </a>
                            </div>
                        @empty
                            <p class="col-span-full text-center text-gray-500">
                                Belum ada data jadwal pelajaran. Silakan tambahkan jadwal terlebih dahulu.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>