<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="text-xl font-bold text-gray-800">Selamat Datang Kembali, {{ Auth::user()->name }}! 👋</h3>
                    <p class="mt-2 text-gray-600">
                        Ini adalah pusat kontrol Anda. Kelola pengguna, jadwal, dan lihat laporan dari sini.
                    </p>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm flex justify-around items-center">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $jumlahGuru ?? 0 }}</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Guru</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $jumlahAkunPiket ?? 0 }}</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Piket</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $jumlahJadwal ?? 0 }}</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Jadwal</div>
                    </div>
                </div>
            </div>

            @if(isset($guruWarning) && !$guruWarning->isEmpty())
                <div class="p-5 bg-yellow-50 border border-yellow-300 rounded-xl shadow-sm" role="alert">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-yellow-800">Peringatan Akumulasi Ketidakhadiran (Bulan Ini)</h4>
                            <ul class="mt-2 list-disc list-inside text-sm text-yellow-700 space-y-1">
                                @foreach($guruWarning as $guru)
                                    <li>
                                        <strong>{{ $guru->name }}</strong> 
                                        @if($guru->total_tidak_hadir == $batasAbsen)
                                            telah <span class="font-semibold">mencapai batas maksimal</span> ketidakhadiran ({{ $guru->total_tidak_hadir }} kali).
                                        @else
                                            telah <span class="font-bold">melebihi batas maksimal</span> ketidakhadiran (total {{ $guru->total_tidak_hadir }} kali).
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <a href="{{ route('display.jadwal') }}" target="_blank" 
                   class="group flex items-center p-6 bg-white rounded-xl shadow-sm hover:bg-indigo-50 transition-all duration-200">
                    <div class="mr-6 bg-indigo-100 p-3 rounded-lg">
                         <svg class="h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-1.621-.871A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25A2.25 2.25 0 015.25 3h9.75a2.25 2.25 0 012.25 2.25z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 group-hover:text-indigo-600">Luncurkan Monitor Real-time</h3>
                        <p class="text-gray-600">Tampilkan status kehadiran guru saat ini di layar monitor.</p>
                    </div>
                </a>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Pintasan Cepat</h3>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Manajemen Pengguna</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-red-50 transition">
                            <div class="flex-shrink-0 bg-red-100 p-3 rounded-lg"><svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800 group-hover:text-red-800">Data Admin</div>
                                <p class="text-xs text-gray-500 mt-1">Kelola akun administrator.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'pimpinan']) }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-purple-50 transition">
                            <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg"><svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.075c0 1.313-.964 2.505-2.287 2.697H5.287c-1.323-.192-2.287-1.384-2.287-2.697v-4.075M12 12.25c-2.485 0-4.5-2.015-4.5-4.5s2.015-4.5 4.5-4.5 4.5 2.015 4.5 4.5-2.015 4.5-4.5 4.5z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800 group-hover:text-purple-800">Data Pimpinan</div>
                                <p class="text-xs text-gray-500 mt-1">Kelola akun pimpinan.</p>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-blue-50 transition">
                            <div class="flex-shrink-0 bg-blue-100 p-3 rounded-lg"><svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-4.663v.003zM12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800 group-hover:text-blue-800">Data Guru</div>
                                <p class="text-xs text-gray-500 mt-1">Kelola semua akun guru.</p>
                            </div>
                        </a>
                    </div>
                </div>

                <hr class="my-8">

                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Manajemen Jadwal</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Jadwal Pelajaran</div>
                                <p class="text-xs text-gray-500 mt-1">Atur jadwal inti mengajar.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                             <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Jadwal Piket</div>
                                <p class="text-xs text-gray-500 mt-1">Atur jadwal piket guru.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h7.5M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Kalender Blok</div>
                                <p class="text-xs text-gray-500 mt-1">Kelola tanggal perblok.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.hari-libur.index') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                           <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.324h5.383c.493 0 .706.656.34.978l-4.36 3.192a.563.563 0 00-.182.635l1.658 5.131a.563.563 0 01-.84.609l-4.38-3.192a.563.563 0 00-.664 0l-4.38 3.192a.563.563 0 01-.84-.609l1.658-5.131a.563.563 0 00-.182-.635l-4.36-3.192a.563.563 0 01.34-.978h5.383a.563.563 0 00.475-.324L11.48 3.5z" /></svg></div>
                           <div>
                               <div class="font-semibold text-gray-800">Hari Libur</div>
                               <p class="text-xs text-gray-500 mt-1">Atur tanggal libur sekolah.</p>
                           </div>
                        </a>
                    </div>
                </div>

                <hr class="my-8">
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Laporan & Arsip</h4>
                     <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.laporan.bulanan') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1.125-1.5M12 16.5v4.5m-3-4.5v4.5m7.5-4.5v4.5m-7.5 0h7.5" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Rekap Bulanan</div>
                                <p class="text-xs text-gray-500 mt-1">Lihat data kehadiran per bulan.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.laporan.mingguan') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 100 15 7.5 7.5 0 000-15zM21 21l-5.197-5.197" /></svg></div>
                             <div>
                                <div class="font-semibold text-gray-800">Rekap Mingguan</div>
                                <p class="text-xs text-gray-500 mt-1">Tinjau data kehadiran per minggu.</p>
                             </div>
                        </a>
                        <a href="{{ route('admin.laporan.individu') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Laporan Individu</div>
                                <p class="text-xs text-gray-500 mt-1">Cari riwayat per individu guru.</p>
                            </div>
                        </a>
                        <a href="{{ route('admin.laporan.arsip') }}" class="group flex items-center gap-4 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                            <div class="flex-shrink-0 bg-slate-200 p-3 rounded-lg"><svg class="h-6 w-6 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg></div>
                            <div>
                                <div class="font-semibold text-gray-800">Arsip Logbook</div>
                                <p class="text-xs text-gray-500 mt-1">Akses catatan lama dari piket.</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-admin-layout>