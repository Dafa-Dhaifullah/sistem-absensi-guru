<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Pimpinan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white p-6 shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                <h3 class="text-lg font-medium text-gray-900">Snapshot Jam Pelajaran Saat Ini</h3>
                @if ($jamKeSekarang)
                    <p class="text-sm text-gray-600">
                        Sedang berlangsung: <strong>Jam ke-{{ $jamKeSekarang->jam_ke }}</strong>
                        ({{ \Carbon\Carbon::parse($jamKeSekarang->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jamKeSekarang->jam_selesai)->format('H:i') }})
                    </p>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-gray-500 uppercase">Kelas Berlangsung</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $snapshotStats['totalKelas'] }}</div>
                        </div>
                        <div class="p-4 bg-green-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-green-800 uppercase">Guru di Kelas</div>
                            <div class="text-3xl font-bold text-green-900">{{ $snapshotStats['guruDiKelas'] }}</div>
                        </div>
                        <div class="p-4 bg-red-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-red-800 uppercase">Kelas Kosong</div>
                            <div class="text-3xl font-bold text-red-900">{{ $snapshotStats['kelasKosong'] }}</div>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-gray-600 font-semibold">Saat ini di luar jam pelajaran atau hari libur.</p>
                @endif
                 <div class="mt-4">
                    <a href="{{ route('admin.laporan.realtime') }}" class="text-sm text-blue-600 hover:text-blue-900 font-medium">
                        Lihat Detail Jadwal Real-time &rarr;
                    </a>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Ringkasan Kehadiran Harian</h3>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('dddd, D MMMM YYYY') }}</p>
                
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="p-4 bg-green-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-green-800 uppercase">Hadir</div>
                        <div class="text-3xl font-bold text-green-900">{{ $summaryHariIni['hadir'] }}</div>
                    </div>
                    <a href="{{ route('admin.laporan.terlambat.harian') }}" class="block p-4 bg-orange-100 rounded-lg text-center hover:shadow-lg transition">
                        <div class="text-sm font-medium text-orange-800 uppercase">Terlambat</div>
                        <div class="text-3xl font-bold text-orange-900">{{ $summaryHariIni['terlambat'] }}</div>
                    </a>
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
                    <div class="p-4 bg-purple-100 rounded-lg text-center">
                        <div class="text-sm font-medium text-purple-800 uppercase">Dinas Luar</div>
                        <div class="text-3xl font-bold text-purple-900">{{ $summaryHariIni['dl'] }}</div>
                    </div>
                </div>
            </div>

            @if(isset($guruWarning) && !$guruWarning->isEmpty())
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-r-lg" role="alert">
                <h4 class="font-bold">Peringatan Akumulasi Ketidakhadiran (Bulan Ini)</h4>
                <p class="text-sm">Guru berikut telah mencapai atau melebihi batas maksimal ketidakhadiran harian (Sakit + Izin + Alpa >= 4 hari):</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach($guruWarning as $guru)
                        <li>
                            <strong>{{ $guru->name }}</strong> 
                            (Total {{ $guru->total_tidak_hadir }} hari tidak hadir)
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Pintasan Menu Laporan -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-xl font-bold text-gray-800">Pintasan Menu Laporan</h3>
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <a href="{{ route('admin.laporan.realtime') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-indigo-50 hover:shadow-sm transition-all duration-200">
                        <div class="mr-4 bg-indigo-100 p-3 rounded-lg"><svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h9.75a2.25 2.25 0 012.25 2.25z" /></svg></div>
                        <div>
                            <div class="font-semibold text-gray-800">Jadwal Real-time</div>
                            <p class="text-sm text-gray-600">Pantau kehadiran guru saat ini.</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.laporan.terlambat.harian') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-orange-50 hover:shadow-sm transition-all duration-200">
                        <div class="mr-4 bg-orange-100 p-3 rounded-lg"><svg class="h-6 w-6 text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                        <div>
                            <div class="font-semibold text-gray-800">Laporan Terlambat</div>
                            <p class="text-sm text-gray-600">Lihat daftar guru yang terlambat.</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.laporan.override_log') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-yellow-50 hover:shadow-sm transition-all duration-200">
                         <div class="mr-4 bg-yellow-100 p-3 rounded-lg"><svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg></div>
                         <div>
                            <div class="font-semibold text-gray-800">Log Aktivitas Piket</div>
                            <p class="text-sm text-gray-600">Lihat riwayat perubahan status.</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.laporan.bulanan') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 hover:shadow-sm transition-all duration-200">
                        <div class="mr-4 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18" /></svg></div>
                        <div>
                            <div class="font-semibold text-gray-800">Rekap Bulanan</div>
                            <p class="text-sm text-gray-600">Analisis data kehadiran per bulan.</p>
                        </div>
                    </a>

                     <a href="{{ route('admin.laporan.mingguan') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 hover:shadow-sm transition-all duration-200">
                        <div class="mr-4 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18" /></svg></div>
                        <div>
                            <div class="font-semibold text-gray-800">Rekap Mingguan</div>
                            <p class="text-sm text-gray-600">Tinjau data kehadiran per minggu.</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.laporan.individu') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 hover:shadow-sm transition-all duration-200">
                         <div class="mr-4 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                         <div>
                            <div class="font-semibold text-gray-800">Laporan Individu</div>
                            <p class="text-sm text-gray-600">Lihat riwayat kehadiran per guru.</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('admin.laporan.arsip') }}" class="group flex items-center p-4 bg-slate-50 rounded-lg hover:bg-slate-100 hover:shadow-sm transition-all duration-200">
                        <div class="mr-4 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></div>
                        <div>
                            <div class="font-semibold text-gray-800">Arsip Logbook</div>
                            <p class="text-sm text-gray-600">Akses catatan harian dari piket.</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
