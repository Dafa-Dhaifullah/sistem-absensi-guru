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
                            
                            <div class="md:col-span-2">
                                <x-input-label for="user_id" :value="__('Pilih Guru')" />
                                <select id="user_id" name="user_id" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled selected>-- Pilih Guru --</option>
                                    @foreach ($semuaGuru as $guru)
                                        <option value="{{ $guru->id }}" 
                                            @if(request('user_id') == $guru->id) selected @endif>
                                            {{ $guru->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="tanggal_mulai" :value="__('Tanggal Mulai')" />
                                <x-text-input id="tanggal_mulai" class="block mt-1 w-full" type="date" name="tanggal_mulai" :value="request('tanggal_mulai')" required />
                            </div>

                            <div>
                                <x-input-label for="tanggal_selesai" :value="__('Tanggal Selesai')" />
                                <x-text-input id="tanggal_selesai" class="block mt-1 w-full" type="date" name="tanggal_selesai" :value="request('tanggal_selesai')" required />
                            </div>

                        </div>
                        <div class="flex items-center gap-4 mt-4">
                            <x-primary-button type="submit">
                                {{ __('Tampilkan Laporan') }}
                            </x-primary-button>
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
                                <h3 class="text-lg font-medium">Hasil Laporan untuk: <span class="text-blue-600">{{ $guruTerpilih->name }}</span></h3>
                                <p class="text-sm text-gray-600">
                                    Periode: {{ \Carbon\Carbon::parse(request('tanggal_mulai'))->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse(request('tanggal_selesai'))->isoFormat('D MMM Y') }}
                                </p>
                            </div>
                            <a href="{{ route('admin.laporan.export.individu', request()->all()) }}"
                               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Export ke Excel
                            </a>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                            <div class="p-4 bg-gray-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-gray-500 uppercase">Total Wajib Hadir</div>
                                <div class="text-3xl font-bold text-gray-900">{{ $summary['Total'] }}</div>
                            </div>
                             <div class="p-4 bg-blue-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-blue-800 uppercase">Hadir</div>
                                <div class="text-3xl font-bold text-blue-900">{{ $summary['Hadir'] }}</div>
                            </div>
                            <div class="p-4 bg-green-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-green-800 uppercase">Sakit</div>
                                <div class="text-3xl font-bold text-green-900">{{ $summary['Sakit'] }}</div>
                            </div>
                            <div class="p-4 bg-yellow-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-yellow-800 uppercase">Izin</div>
                                <div class="text-3xl font-bold text-yellow-900">{{ $summary['Izin'] }}</div>
                            </div>
                            <div class="p-4 bg-red-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-red-800 uppercase">Alpa</div>
                                <div class="text-3xl font-bold text-red-900">{{ $summary['Alpa'] }}</div>
                            </div>
                            <div class="p-4 bg-purple-100 rounded-lg text-center">
                                <div class="text-sm font-medium text-purple-800 uppercase">Dinas Luar</div>
                                <div class="text-3xl font-bold text-purple-900">{{ $summary['DL'] }}</div>
                            </div>
                        </div>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($laporan as $log)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($log->tanggal)->isoFormat('D MMMM YYYY') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($log->tanggal)->locale('id_ID')->isoFormat('dddd') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @php
                                                    $statusColor = 'bg-gray-100 text-gray-800';
                                                    if ($log->status == 'Hadir') $statusColor = 'bg-blue-100 text-blue-800';
                                                    if ($log->status == 'Sakit') $statusColor = 'bg-green-100 text-green-800';
                                                    if ($log->status == 'Izin') $statusColor = 'bg-yellow-100 text-yellow-800';
                                                    if ($log->status == 'Alpa') $statusColor = 'bg-red-100 text-red-800';
                                                    if ($log->status == 'DL') $statusColor = 'bg-purple-100 text-purple-800';
                                                @endphp
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                    {{ $log->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                                Tidak ada data laporan pada rentang tanggal ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 text-center">
                        Silakan pilih guru dan rentang tanggal di atas untuk melihat laporan.
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-admin-layout>