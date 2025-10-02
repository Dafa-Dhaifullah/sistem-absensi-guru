<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium">Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Ini adalah panel kontrol Anda. Silakan kelola sistem melalui menu di bawah.
                    </p>

                    @if(isset($guruWarning) && !$guruWarning->isEmpty())
                    <div class="mt-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-r-lg shadow-md" role="alert">
                        <h4 class="font-bold">Peringatan Ketidakhadiran (Bulan Ini)</h4>
                        <p class="text-sm">Guru berikut telah mencapai atau melebihi batas maksimal ketidakhadiran gabungan (Sakit + Izin + Alpa >= 4 kali):</p>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach($guruWarning as $guru)
                                <li>
                                    <strong>{{ $guru->name }}</strong> 
                                    (Total {{ $guru->laporan_harian_count }} kali tidak hadir)
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <a href="{{ route('display.jadwal') }}" target="_blank" 
                           class="block w-full p-6 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition duration-150">
                            <h3 class="text-2xl font-bold">Luncurkan Monitor Real-time</h3>
                            <p class="text-indigo-100">Menampilkan status kehadiran guru saat ini.</p>
                        </a>
                        
                        <a href="{{ route('display.qr-kios') }}" target="_blank" 
                           class="block w-full p-6 bg-gray-700 text-white rounded-lg shadow-md hover:bg-gray-800 transition duration-150">
                            <h3 class="text-2xl font-bold">Tampilkan QR Code Absensi</h3>
                            <p class="text-gray-300">Buka halaman ini di monitor/tablet untuk absensi.</p>
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 bg-gray-50 rounded-lg shadow border-l-4 border-gray-300">
                            <div class="text-sm font-medium text-gray-500 uppercase">Total Guru</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahGuru ?? 0 }}</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg shadow border-l-4 border-gray-300">
                            <div class="text-sm font-medium text-gray-500 uppercase">Akun Piket</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahAkunPiket ?? 0 }}</div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg shadow border-l-4 border-gray-300">
                            <div class="text-sm font-medium text-gray-500 uppercase">Total Jadwal</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahJadwal ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Pintasan Manajemen Data Pengguna</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('admin.pengguna.index', ['role' => 'admin']) }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-red-400 hover:border-red-500 transition-colors">
                            <div class="font-semibold text-gray-800">Data Admin</div>
                        </a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'kepala_sekolah']) }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-purple-400 hover:border-purple-500 transition-colors">
                            <div class="font-semibold text-gray-800">Data Kepala Sekolah</div>
                        </a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'piket']) }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-yellow-400 hover:border-yellow-500 transition-colors">
                            <div class="font-semibold text-gray-800">Data Guru Piket</div>
                        </a>
                        <a href="{{ route('admin.pengguna.index', ['role' => 'guru']) }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-blue-400 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Data Guru Umum</div>
                        </a>
                    </div>
                    
                    <h3 class="text-lg font-medium text-gray-900 mt-8">Pintasan Manajemen Jadwal (Otak Sistem)</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="block p-6 bg-gray-50 hover:bg-gray-100 rounded-lg border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Manajemen Jadwal Pelajaran (Inti)</div>
                        </a>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Manajemen Jadwal Piket</div>
                        </a>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Manajemen Kalender Blok</div>
                        </a>
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 mt-8">Pintasan Laporan</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.laporan.bulanan') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Laporan Rekap Bulanan</div>
                        </a>
                        <a href="{{ route('admin.laporan.mingguan') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Laporan Rekap Mingguan</div>
                        </a>
                        <a href="{{ route('admin.laporan.individu') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Laporan Individu Guru</div>
                        </a>
                        <a href="{{ route('admin.laporan.arsip') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm border-l-4 border-gray-300 hover:border-blue-500 transition-colors">
                            <div class="font-semibold text-gray-800">Arsip Logbook Piket</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>