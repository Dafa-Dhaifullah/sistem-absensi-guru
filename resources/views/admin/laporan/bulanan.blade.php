<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Rekapitulasi Bulanan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Filter Laporan</h3>
                    <form action="{{ route('admin.laporan.bulanan') }}" method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="bulan" :value="__('Bulan')" />
                                <select name="bulan" id="bulan" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->isoFormat('MMMM') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <x-input-label for="tahun" :value="__('Tahun')" />
                                <x-text-input id="tahun" class="block mt-1 w-full" type="number" name="tahun" :value="$tahun" required />
                            </div>
                            <div class="flex items-end">
                                <x-primary-button type="submit">{{ __('Tampilkan') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">
                            Menampilkan Laporan: {{ \Carbon\Carbon::create()->month($bulan)->isoFormat('MMMM') }} {{ $tahun }}
                        </h3>
                        <a href="{{ route('admin.laporan.export.bulanan', ['bulan' => $bulan, 'tahun' => $tahun]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Export ke Excel
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">Nama Guru</th>
                                    @for ($i = 1; $i <= $daysInMonth; $i++)
                                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r">{{ $i }}</th>
                                    @endfor
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-blue-50 border-r">H</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-green-50 border-r">S</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50 border-r">I</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-red-50 border-r">A</th>
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">DL</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaGuru as $guru)
                                    <tr>
                                        <!-- REVISI: Panggil kolom 'name' dari model User -->
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-r">{{ $guru->name }}</td>
                                        @for ($i = 1; $i <= $daysInMonth; $i++)
                                            @php
                                                $tanggalCek = sprintf('%s-%s-%s', $tahun, str_pad($bulan, 2, '0', STR_PAD_LEFT), str_pad($i, 2, '0', STR_PAD_LEFT));
                                                $laporan = $guru->laporanHarian->firstWhere('tanggal', $tanggalCek);
                                                $status = $laporan ? $laporan->status : '';
                                                
                                                $bgColor = 'bg-white';
                                                if ($status == 'Hadir') $bgColor = 'bg-blue-100';
                                                if ($status == 'Sakit') $bgColor = 'bg-green-100 text-green-800';
                                                if ($status == 'Izin') $bgColor = 'bg-yellow-100 text-yellow-800';
                                                if ($status == 'Alpa') $bgColor = 'bg-red-200 text-red-800 font-bold';
                                                if ($status == 'DL') $bgColor = 'bg-gray-200';
                                            @endphp
                                            <td class="px-2 py-3 text-center text-xs font-medium border-r {{ $bgColor }}">
                                                {{ $status ? substr($status, 0, 1) : '-' }}
                                            </td>
                                        @endfor
                                        @php
                                            $totalHadir = $guru->laporanHarian->where('status', 'Hadir')->count();
                                            $totalSakit = $guru->laporanHarian->where('status', 'Sakit')->count();
                                            $totalIzin = $guru->laporanHarian->where('status', 'Izin')->count();
                                            $totalAlpa = $guru->laporanHarian->where('status', 'Alpa')->count();
                                            $totalDL = $guru->laporanHarian->where('status', 'DL')->count();
                                        @endphp
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-blue-50">{{ $totalHadir }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-green-50">{{ $totalSakit }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-yellow-50">{{ $totalIzin }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium border-r bg-red-50">{{ $totalAlpa }}</td>
                                        <td class="px-2 py-3 text-center text-xs font-medium bg-gray-100">{{ $totalDL }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $daysInMonth + 6 }}" class="px-6 py-4 text-center text-gray-500">Belum ada data guru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
