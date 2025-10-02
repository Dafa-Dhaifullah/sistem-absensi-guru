<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Individu Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Filter Laporan</h3>
                    <form action="{{ route('admin.laporan.individu') }}" method="GET">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- REVISI: Ganti 'data_guru_id' menjadi 'user_id' -->
                            <div class="md:col-span-2">
                                <x-input-label for="user_id" :value="__('Pilih Guru')" />
                                <select id="user_id" name="user_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled selected>-- Pilih Guru --</option>
                                    @foreach ($semuaGuru as $guru)
                                        <option value="{{ $guru->id }}" @if($request->user_id == $guru->id) selected @endif>
                                            {{ $guru->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="tanggal_mulai" :value="__('Tanggal Mulai')" />
                                <x-text-input id="tanggal_mulai" class="block mt-1 w-full" type="date" name="tanggal_mulai" :value="$request->tanggal_mulai" required />
                            </div>
                            <div>
                                <x-input-label for="tanggal_selesai" :value="__('Tanggal Selesai')" />
                                <x-text-input id="tanggal_selesai" class="block mt-1 w-full" type="date" name="tanggal_selesai" :value="$request->tanggal_selesai" required />
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mt-4">
                            <x-primary-button type="submit">{{ __('Tampilkan Laporan') }}</x-primary-button>
                            <a href="{{ route('admin.laporan.individu') }}" class="text-gray-600 hover:text-gray-900 text-sm">{{ __('Reset Filter') }}</a>
                        </div>
                    </form>
                </div>
            </div>

            @if ($laporan)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <!-- REVISI: Panggil 'name' -->
                                <h3 class="text-lg font-medium">Hasil Laporan untuk: <span class="text-blue-600">{{ $guruTerpilih->name }}</span></h3>
                                <p class="text-sm text-gray-600">
                                    Periode: {{ \Carbon\Carbon::parse($request->tanggal_mulai)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($request->tanggal_selesai)->isoFormat('D MMM Y') }}
                                </p>
                            </div>
                            <a href="{{ route('admin.laporan.export.individu', $request->all()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-xs text-white uppercase font-semibold rounded-md hover:bg-green-700">
                                Export ke Excel
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                            <div class="p-4 bg-gray-100 rounded-lg text-center"><div class="text-sm uppercase">Total Wajib Hadir</div><div class="text-3xl font-bold">{{ $summary['Total'] }}</div></div>
                             <div class="p-4 bg-blue-100 rounded-lg text-center"><div class="text-sm uppercase">Hadir</div><div class="text-3xl font-bold">{{ $summary['Hadir'] }}</div></div>
                            <div class="p-4 bg-green-100 rounded-lg text-center"><div class="text-sm uppercase">Sakit</div><div class="text-3xl font-bold">{{ $summary['Sakit'] }}</div></div>
                            <div class="p-4 bg-yellow-100 rounded-lg text-center"><div class="text-sm uppercase">Izin</div><div class="text-3xl font-bold">{{ $summary['Izin'] }}</div></div>
                            <div class="p-4 bg-red-100 rounded-lg text-center"><div class="text-sm uppercase">Alpa</div><div class="text-3xl font-bold">{{ $summary['Alpa'] }}</div></div>
                        </div>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($laporan as $log)
                                        <tr>
                                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($log->tanggal)->isoFormat('D MMMM YYYY') }}</td>
                                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($log->tanggal)->isoFormat('dddd') }}</td>
                                            <td class="px-6 py-4">
                                                @php
                                                    $bgColor = 'bg-gray-100 text-gray-800';
                                                    if ($log->status == 'Hadir') $bgColor = 'bg-blue-100 text-blue-800';
                                                    if ($log->status == 'Sakit') $bgColor = 'bg-green-100 text-green-800';
                                                    if ($log->status == 'Izin') $bgColor = 'bg-yellow-100 text-yellow-800';
                                                    if ($log->status == 'Alpa') $bgColor = 'bg-red-100 text-red-800';
                                                    if ($log->status == 'DL') $bgColor = 'bg-gray-100 text-gray-800';
                                                @endphp
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $bgColor }}">
                                                    {{ $log->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada data laporan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"><div class="p-6 text-center">Silakan pilih guru dan rentang tanggal di atas untuk melihat laporan.</div></div>
            @endif
        </div>
    </div>
</x-admin-layout>
