<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Rekapitulasi Mingguan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Filter Laporan</h3>
                    <form action="{{ route('admin.laporan.mingguan.sesi') }}" method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="tanggal_mulai" :value="__('Tanggal Mulai')" />
                                <x-text-input id="tanggal_mulai" class="block mt-1 w-full" type="date" name="tanggal_mulai" :value="$tanggalMulai" required />
                            </div>
                            <div>
                                <x-input-label for="tanggal_selesai" :value="__('Tanggal Selesai')" />
                                <x-text-input id="tanggal_selesai" class="block mt-1 w-full" type="date" name="tanggal_selesai" :value="$tanggalSelesai" required />
                            </div>
                            <div class="flex items-end">
                                <x-primary-button type="submit">{{ __('Tampilkan') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-6">
                <div class="hidden sm:block">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <a href="{{ route('admin.laporan.mingguan', request()->query()) }}" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Tampilan Harian 
                            </a>
                            <a href="{{ route('admin.laporan.mingguan.sesi', request()->query()) }}" class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" aria-current="page">
                                Tampilan per Jadwal
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">
                            Menampilkan Laporan Sesi: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMM Y') }}
                        </h3>
                        <a href="{{ route('admin.laporan.export.mingguan-sesi', request()->query()) }}"  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Export ke Excel
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Guru</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total Jadwal Wajib</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jadwal Hadir</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jadwal Terlambat</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sakit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Izin</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Alpa</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Dinas Luar</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">% Kehadiran</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">% Ketepatan Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($laporanPerSesi as $laporan)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $laporan['name'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900 font-bold">{{ $laporan['totalSesiWajib'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $laporan['totalHadir'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-orange-600">({{ $laporan['totalTerlambat'] }})</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $laporan['totalSakit'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $laporan['totalIzin'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $laporan['totalAlpa'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">{{ $laporan['totalDL'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-bold {{ $laporan['persentaseHadir'] >= 90 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $laporan['persentaseHadir'] }}%
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm font-bold {{ $laporan['persentaseTepatWaktu'] >= 90 ? 'text-green-600' : 'text-orange-600' }}">
                                            {{ $laporan['persentaseTepatWaktu'] }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="px-6 py-4 text-center text-gray-500">Belum ada data guru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>