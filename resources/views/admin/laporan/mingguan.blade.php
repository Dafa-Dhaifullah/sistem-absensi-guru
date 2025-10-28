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
                <a href="{{ route('admin.laporan.mingguan', request()->query()) }}" class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" aria-current="page">
                    Tampilan Harian 
                </a>
                <a href="{{ route('admin.laporan.mingguan.sesi', request()->query()) }}" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
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
                            Menampilkan Laporan: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMM Y') }}
                        </h3>
                        <a href="{{ route('admin.laporan.export.mingguan', ['tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai]) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Export ke Excel
                        </a>
                    </div>
                    
                    <div class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                        <span class="font-semibold">Keterangan:</span>
                        <span class="inline-flex items-center"><div class="w-3 h-3 rounded-full bg-green-100 border mr-2"></div> Hadir</span>
                        <span class="inline-flex items-center"><div class="w-3 h-3 rounded-full bg-yellow-100 border mr-2"></div> Sakit</span>
                        <span class="inline-flex items-center"><div class="w-3 h-3 rounded-full bg-blue-100 border mr-2"></div> Izin</span>
                        <span class="inline-flex items-center"><div class="w-3 h-3 rounded-full bg-red-100 border mr-2"></div> Alpa</span>
                        <span class="inline-flex items-center"><div class="w-3 h-3 rounded-full bg-purple-100 border mr-2"></div> Dinas Luar</span>
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
                                    
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-green-50">H</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-yellow-50">S</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-blue-50">I</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-red-50">A</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase bg-purple-50">DL</th>
__
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                
                                @php
                                    $summaryKeys = array_keys($summaryTotal);
                                @endphp
                                @forelse ($laporanHarianTeringkas as $laporan)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">{{ $laporan['name'] }}</td>

                                        @foreach ($tanggalRange as $tanggal)
                                            @php
                                                $status = $laporan['dataHarian'][$tanggal->toDateString()];
                                                $bgColor = 'bg-white';
                                                if ($status == 'H') $bgColor = 'bg-green-100';
                                                if ($status == 'S') $bgColor = 'bg-yellow-100';
                                                if ($status == 'I') $bgColor = 'bg-blue-100';
                                                if ($status == 'A') $bgColor = 'bg-red-100 font-bold';
                                                if ($status == 'DL') $bgColor = 'bg-purple-100';
                                            @endphp
                                            <td class="px-2 py-3 text-center text-xs font-medium border-r {{ $bgColor }}">
                                                {{ $status }}
                                            </td>
                                        @endforeach
                                        
                                        @php
                                            $currentKey = $summaryKeys[$loop->index];
                                            $summary = $summaryTotal[$currentKey];
                                        @endphp
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-green-50">{{ $summary['totalHadir'] }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-yellow-50">{{ $summary['totalSakit'] }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-blue-50">{{ $summary['totalIzin'] }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-red-50">{{ $summary['totalAlpa'] }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium bg-purple-50">{{ $summary['totalDL'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($tanggalRange) + 6 }}" class="px-6 py-4 text-center text-sm text-gray-500">
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