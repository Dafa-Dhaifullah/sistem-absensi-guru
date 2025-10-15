<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Pengguna: {{ $pengguna->name }}
            </h2>
            <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Kembali</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Informasi Akun</h3>
                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Nama Lengkap</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $pengguna->name }}</dd>
                        </div>
                         <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Username</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $pengguna->username }}</dd>
                        </div>
                         <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Hak Akses (Role)</dt>
                            <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $pengguna->role }}</dd>
                        </div>
                         <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">NIP</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $pengguna->nip ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $pengguna->email ?? '-' }}</dd>
                        </div>
                         <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">No. WhatsApp</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $pengguna->no_wa ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Statistik Kehadiran Bulan Ini ({{ now()->isoFormat('MMMM YYYY') }})</h3>
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="p-4 bg-green-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-green-800 uppercase">Hadir</div>
                            <div class="text-3xl font-bold text-green-900">{{ $summary['hadir'] }}</div>
                        </div>
                        <div class="p-4 bg-orange-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-orange-800 uppercase">Terlambat</div>
                            <div class="text-3xl font-bold text-orange-900">{{ $summary['terlambat'] }}</div>
                        </div>
                        <div class="p-4 bg-yellow-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-yellow-800 uppercase">Sakit</div>
                            <div class="text-3xl font-bold text-yellow-900">{{ $summary['sakit'] }}</div>
                        </div>
                        <div class="p-4 bg-blue-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-blue-800 uppercase">Izin</div>
                            <div class="text-3xl font-bold text-blue-900">{{ $summary['izin'] }}</div>
                        </div>
                        <div class="p-4 bg-red-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-red-800 uppercase">Alpa</div>
                            <div class="text-3xl font-bold text-red-900">{{ $summary['alpa'] }}</div>
                        </div>
                        <div class="p-4 bg-purple-100 rounded-lg text-center">
                            <div class="text-sm font-medium text-purple-800 uppercase">Dinas Luar</div>
                            <div class="text-3xl font-bold text-purple-900">{{ $summary['dl'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>