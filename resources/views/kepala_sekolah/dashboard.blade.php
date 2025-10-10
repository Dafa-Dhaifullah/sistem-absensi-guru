<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Kepala Sekolah') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- Ringkasan Statistik Kehadiran Hari Ini -->
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-xl font-bold text-gray-800">Ringkasan Kehadiran Hari Ini</h3>
                <p class="text-sm text-gray-500">{{ \Carbon\Carbon::now('Asia/Jakarta')->Locale('id_ID')->isoFormat('dddd, D MMMM YYYY') }}</p>
                
                <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5">
                    <!-- Hadir -->
                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                        <div class="text-3xl font-bold text-green-800">{{ $summaryHariIni['hadir'] }}</div>
                        <div class="text-sm font-medium text-green-700 uppercase tracking-wider">Hadir</div>
                    </div>
                    <!-- Terlambat -->
                    <a href="{{ route('admin.laporan.terlambat.harian') }}" class="block p-4 bg-orange-50 border border-orange-200 rounded-lg text-center hover:shadow-md transition">
                        <div class="text-3xl font-bold text-orange-800">{{ $summaryHariIni['terlambat'] }}</div>
                        <div class="text-sm font-medium text-orange-700 uppercase tracking-wider">Terlambat</div>
                    </a>
                    <!-- Sakit -->
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                        <div class="text-3xl font-bold text-yellow-800">{{ $summaryHariIni['sakit'] }}</div>
                        <div class="text-sm font-medium text-yellow-700 uppercase tracking-wider">Sakit</div>
                    </div>
                    <!-- Izin -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
                        <div class="text-3xl font-bold text-blue-800">{{ $summaryHariIni['izin'] }}</div>
                        <div class="text-sm font-medium text-blue-700 uppercase tracking-wider">Izin</div>
                    </div>
                    <!-- Alpa -->
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-center">
                        <div class="text-3xl font-bold text-red-800">{{ $summaryHariIni['alpa'] }}</div>
                        <div class="text-sm font-medium text-red-700 uppercase tracking-wider">Alpa</div>
                    </div>
                    <!-- Dinas Luar -->
                    <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg text-center">
                        <div class="text-3xl font-bold text-purple-800">{{ $summaryHariIni['dl'] }}</div>
                        <div class="text-sm font-medium text-purple-700 uppercase tracking-wider">Dinas Luar</div>
                    </div>
                </div>
            </div>

            <!-- Peringatan Akumulasi Ketidakhadiran -->
            @if(isset($guruWarning) && !$guruWarning->isEmpty())
            <div class="p-5 bg-yellow-50 border border-yellow-300 rounded-xl shadow-sm" role="alert">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-yellow-800">Peringatan Akumulasi Ketidakhadiran (Bulan Ini)</h4>
                        <p class="text-sm text-yellow-700 mt-1">Guru berikut telah mencapai atau melebihi batas maksimal ketidakhadiran (Sakit + Izin + Alpa >= 4 kali):</p>
                        <ul class="mt-2 list-disc list-inside text-sm text-yellow-700">
                            @foreach($guruWarning as $guru)
                                <li>
                                    <strong>{{ $guru->name }}</strong> 
                                    (Total {{ $guru->total_tidak_hadir }} kali tidak hadir)
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
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
