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
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Guru Piket</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ $jumlahJadwal ?? 0 }}</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Jadwal</div>
                    </div>
                </div>
            </div>

            @if(isset($guruWarning) && !$guruWarning->isEmpty())
            <div class="p-5 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-lg shadow-sm" role="alert">
                <div class="flex">
                    <div class="py-1">
                        <svg class="h-6 w-6 text-yellow-500 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-yellow-800">Peringatan Ketidakhadiran (Bulan Ini)</h4>
                        <p class="text-sm text-yellow-700">Beberapa guru telah melebihi batas maksimal ketidakhadiran:</p>
                        <ul class="mt-2 list-disc list-inside text-sm text-yellow-700">
                            @foreach($guruWarning as $guru)
                                <li>
                                    <strong>{{ $guru->name }}</strong> 
                                    (Total {{ $guru->laporan_harian_count }} kali tidak hadir)
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
                        <p class="text-gray-600">Tampilkan status kehadiran guru saat ini.</p>
                    </div>
                </a>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Pintasan Cepat</h3>
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Manajemen Pengguna</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="shortcut-card bg-red-50 hover:bg-red-100 text-red-800">Data Admin</a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}" class="shortcut-card bg-purple-50 hover:bg-purple-100 text-purple-800">Data Kepala Sekolah</a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}" class="shortcut-card bg-yellow-50 hover:bg-yellow-100 text-yellow-800">Data Guru Piket</a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="shortcut-card bg-blue-50 hover:bg-blue-100 text-blue-800">Data Guru Umum</a>
                    </div>
                </div>

                <hr class="my-8">

                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Manajemen Jadwal</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Jadwal Pelajaran (Inti)</a>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Jadwal Piket</a>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Kalender Blok</a>
                        <a href="{{ route('admin.hari-libur.index') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Hari Libur</a>
                    </div>
                </div>

                <hr class="my-8">
                
                <div>
                    <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Laporan & Arsip</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.laporan.bulanan') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Rekap Bulanan</a>
                        <a href="{{ route('admin.laporan.mingguan') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Rekap Mingguan</a>
                        <a href="{{ route('admin.laporan.individu') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Laporan Individu</a>
                        <a href="{{ route('admin.laporan.arsip') }}" class="shortcut-card bg-slate-50 hover:bg-slate-100 text-slate-800">Arsip Logbook</a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-admin-layout>