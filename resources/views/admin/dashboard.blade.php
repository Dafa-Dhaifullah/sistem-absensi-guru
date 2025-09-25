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
                <strong>{{ $guru->nama_guru }}</strong> 
                (Total {{ $guru->laporan_harian_count }} kali tidak hadir)
            </li>
        @endforeach
    </ul>
</div>
@endif
                    <a href="{{ route('display.jadwal') }}" target="_blank" 
                       class="block w-full p-6 bg-blue-600 text-white rounded-lg shadow-md hover:bg-blue-700 transition duration-150 mt-6">
                        <h3 class="text-2xl font-bold">Luncurkan Monitor Real-time</h3>
                        <p class="text-blue-100">Klik di sini untuk membuka halaman jadwal (untuk ditampilkan di monitor depan) di tab baru.</p>
                    </a>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-gray-800 uppercase">Total Guru</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahGuru ?? 0 }}</div>
                        </div>
                        <div class="p-4 bg-gray-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-gray-800 uppercase">Akun Piket</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahAkunPiket ?? 0 }}</div>
                        </div>
                        <div class="p-4 bg-gray-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-gray-800 uppercase">Total Jadwal</div>
                            <div class="text-3xl font-bold text-gray-900">{{ $jumlahJadwal ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Pintasan Manajemen Data Master</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.data-guru.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Manajemen Data Guru</div>
                            <div class="text-sm text-gray-600">Kelola daftar master guru.</div>
                        </a>
                        <a href="{{ route('admin.akun-piket.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Manajemen Akun Piket</div>
                            <div class="text-sm text-gray-600">Kelola akun login untuk guru piket.</div>
                        </a>
                        <a href="{{ route('admin.akun-admin.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Manajemen Akun Admin</div>
                            <div class="text-sm text-gray-600">Kelola akun admin lain.</div>
                        </a>
                    </div>
                    
                    <h3 class="text-lg font-medium text-gray-900 mt-8">Pintasan Manajemen Jadwal (Otak Sistem)</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="block p-6 bg-yellow-50 hover:bg-yellow-100 rounded-lg border border-yellow-200 shadow-sm">
                            <div class="font-semibold text-yellow-800">Manajemen Jadwal Pelajaran (Inti)</div>
                            <div class="text-sm text-yellow-600">Atur jadwal mengajar, blok, dan jam.</div>
                        </a>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Manajemen Jadwal Piket</div>
                            <div class="text-sm text-gray-600">Atur template piket mingguan.</div>
                        </a>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Manajemen Kalender Blok</div>
                            <div class="text-sm text-gray-600">Atur rentang tanggal Minggu 1 & 2.</div>
                        </a>
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 mt-8">Pintasan Laporan</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('admin.laporan.bulanan') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Laporan Rekap Bulanan</div>
                        </a>
                        <a href="{{ route('admin.laporan.mingguan') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Laporan Rekap Mingguan</div>
                        </a>
                        <a href="{{ route('admin.laporan.individu') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Laporan Individu Guru</div>
                        </a>
                        <a href="{{ route('admin.laporan.arsip') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg shadow-sm">
                            <div class="font-semibold text-gray-800">Arsip Logbook Piket</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>