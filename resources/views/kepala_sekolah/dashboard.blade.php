<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Kepala Sekolah') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Ringkasan Hari Ini -->
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Ringkasan Kehadiran Hari Ini</h3>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('dddd, D MMMM YYYY') }}</p>
                
                <!-- REVISI: Grid diubah menjadi 6 kolom agar rapi -->
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="p-4 bg-green-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-green-800 uppercase">Hadir</div>
                        <div class="text-3xl font-bold text-green-900">{{ $summaryHariIni['hadir'] }}</div>
                    </div>
                    <div class="p-4 bg-orange-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-orange-800 uppercase">Terlambat</div>
                        <div class="text-3xl font-bold text-orange-900">{{ $summaryHariIni['terlambat'] }}</div>
                    </div>
                    <div class="p-4 bg-yellow-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-yellow-800 uppercase">Sakit</div>
                        <div class="text-3xl font-bold text-yellow-900">{{ $summaryHariIni['sakit'] }}</div>
                    </div>
                    <div class="p-4 bg-blue-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-blue-800 uppercase">Izin</div>
                        <div class="text-3xl font-bold text-blue-900">{{ $summaryHariIni['izin'] }}</div>
                    </div>
                    <div class="p-4 bg-red-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-red-800 uppercase">Alpa</div>
                        <div class="text-3xl font-bold text-red-900">{{ $summaryHariIni['alpa'] }}</div>
                    </div>
                    <!-- TAMBAHAN BARU: Kartu Dinas Luar -->
                    <div class="p-4 bg-purple-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-purple-800 uppercase">Dinas Luar</div>
                        <div class="text-3xl font-bold text-purple-900">{{ $summaryHariIni['dl'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Notifikasi Warning (Tidak ada perubahan) -->
            @if(isset($guruWarning) && !$guruWarning->isEmpty())
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-r-lg" role="alert">
                <h4 class="font-bold">Peringatan Akumulasi Ketidakhadiran (Bulan Ini)</h4>
                <p class="text-sm">Guru berikut telah mencapai atau melebihi batas maksimal ketidakhadiran (Sakit + Izin + Alpa >= 4 kali):</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach($guruWarning as $guru)
                        <li>
                            <strong>{{ $guru->name }}</strong> 
                            (Total {{ $guru->total_tidak_hadir }} kali tidak hadir)
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Pintasan Laporan (Tidak ada perubahan) -->
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Pintasan Menu Laporan</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('admin.laporan.realtime') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-blue-400">
                        <div class="font-semibold text-gray-800">Lihat Jadwal Real-time</div>
                    </a>
                    <a href="{{ route('admin.laporan.bulanan') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-400">
                        <div class="font-semibold text-gray-800">Buka Rekap Bulanan</div>
                    </a>
                     <a href="{{ route('admin.laporan.individu') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-400">
                        <div class="font-semibold text-gray-800">Lihat Laporan Individu</div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
