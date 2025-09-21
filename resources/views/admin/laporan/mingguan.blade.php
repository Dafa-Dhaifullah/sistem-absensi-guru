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
                    <form action="{{ route('admin.laporan.mingguan') }}" method="GET">
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
                                <x-primary-button type="submit">
                                    {{ __('Tampilkan') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">
                            Menampilkan Laporan: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMM Y') }}
                        </h3>
                        <a href="{{ route('admin.laporan.export.mingguan', ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai]) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Export ke Excel
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Nama Guru</th>
                                    
                                    @foreach ($tanggalRange as $tanggal)
                                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                            {{ $tanggal->isoFormat('ddd, D') }}
                                        </th>
                                    @endforeach
                                    
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">Total Absen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                
                                @forelse ($semuaGuru as $guru)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">{{ $guru->nama_guru }}</td>

                                        @foreach ($tanggalRange as $tanggal)
                                            @php
                                                // Cari laporan di tanggal itu
                                                $laporan = $guru->laporanHarian->firstWhere('tanggal', $tanggal->toDateString());
                                                $status = $laporan ? $laporan->status : '';
                                                
                                                // Tentukan warna cell
                                                $bgColor = 'bg-white'; // Default (Tidak ada jadwal)
                                                if ($status == 'Hadir') $bgColor = 'bg-blue-100';
                                                if ($status == 'Sakit') $bgColor = 'bg-green-100 text-green-800';
                                                if ($status == 'Izin') $bgColor = 'bg-yellow-100 text-yellow-800';
                                                if ($status == 'Alpa') $bgColor = 'bg-red-200 text-red-800 font-bold';
                                                if ($status == 'DL') $bgColor = 'bg-gray-200';
                                            @endphp
                                            <td class="px-2 py-3 text-center text-xs font-medium border-r {{ $bgColor }}">
                                                {{ $status ? substr($status, 0, 1) : '-' }}
                                            </td>
                                        @endforeach
                                        
                                        <td class="px-2 py-3 text-center text-sm font-medium bg-gray-50">
                                            H: <span class="font-bold text-blue-600">{{ $guru->laporanHarian->where('status', 'Hadir')->count() }}</span> | 
                                            S: <span class="font-bold text-green-600">{{ $guru->laporanHarian->where('status', 'Sakit')->count() }}</span> | 
                                            I: <span class="font-bold text-yellow-600">{{ $guru->laporanHarian->where('status', 'Izin')->count() }}</span> | 
                                            A: <span class="font-bold text-red-600">{{ $guru->laporanHarian->where('status', 'Alpa')->count() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $tanggalRange->count() + 2 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            Belum ada data guru.
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>