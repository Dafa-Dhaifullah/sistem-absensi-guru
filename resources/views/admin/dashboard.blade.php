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

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-blue-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-blue-800 uppercase">Total Guru</div>
                            <div class="text-3xl font-bold text-blue-900">{{ $jumlahGuru }}</div>
                        </div>
                        <div class="p-4 bg-green-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-green-800 uppercase">Akun Piket</div>
                            <div class="text-3xl font-bold text-green-900">{{ $jumlahAkunPiket }}</div>
                        </div>
                        <div class="p-4 bg-indigo-100 rounded-lg shadow">
                            <div class="text-sm font-medium text-indigo-800 uppercase">Total Jadwal</div>
                            <div class="text-3xl font-bold text-indigo-900">{{ $jumlahJadwal }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Pintasan Manajemen</h3>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <a href="{{ route('admin.data-guru.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-semibold text-gray-800">Manajemen Data Guru</div>
                            <div class="text-sm text-gray-600">Kelola daftar master guru.</div>
                        </a>
                        <a href="{{ route('admin.akun-piket.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-semibold text-gray-800">Manajemen Akun Piket</div>
                            <div class="text-sm text-gray-600">Kelola akun login untuk guru piket.</div>
                        </a>
                        
                        <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="block p-6 bg-yellow-50 hover:bg-yellow-100 rounded-lg border border-yellow-200">
                            <div class="font-semibold text-yellow-800">Manajemen Jadwal Pelajaran (Inti)</div>
                            <div class="text-sm text-yellow-600">Atur jadwal mengajar, blok, dan jam.</div>
                        </a>
                        <a href="{{ route('admin.jadwal-piket.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-semibold text-gray-800">Manajemen Jadwal Piket</div>
                            <div class="text-sm text-gray-600">Atur template piket mingguan.</div>
                        </a>
                        <a href="{{ route('admin.kalender-blok.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-semibold text-gray-800">Manajemen Kalender Blok</div>
                            <div class="text-sm text-gray-600">Atur rentang tanggal Minggu 1 & 2.</div>
                        </a>
                        
                        <a href="{{ route('admin.akun-admin.index') }}" class="block p-4 bg-gray-50 hover:bg-gray-100 rounded-lg">
                            <div class="font-semibold text-gray-800">Manajemen Akun Admin</div>
                            <div class="text-sm text-gray-600">Kelola akun admin lain.</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>