<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jadwal Pelajaran Real-time') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-lg font-medium text-blue-900">
                            Menampilkan Jadwal untuk: {{ $hariIni }}
                        </h3>
                        @if ($jamKeSekarang)
                            <p class="text-gray-700">
                                Saat ini: <strong>Jam ke-{{ $jamKeSekarang->jam_ke }}</strong>
                                ({{ Carbon\Carbon::parse($jamKeSekarang->jam_mulai)->format('H:i') }} - {{ Carbon\Carbon::parse($jamKeSekarang->jam_selesai)->format('H:i') }})
                                | Blok: <strong>{{ $tipeMinggu }}</strong>
                            </p>
                        @else
                            <p class="text-gray-700 font-semibold">
                                Di luar jam pelajaran.
                            </p>
                        @endif
                    </div>

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guru Pengajar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                    <!-- TAMBAHAN BARU: Header Status Kehadiran -->
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                
                                @forelse ($jadwalSekarang as $jadwal)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $jadwal->kelas }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->user->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->mata_pelajaran ?? '-' }}</td>
                                        <!-- TAMBAHAN BARU: Kolom Status Kehadiran -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @php
                                                $laporan = $laporanHariIni->get($jadwal->user_id);
                                                $status = $laporan ? $laporan->status : 'Belum Absen';
                                                
                                                $statusColor = 'bg-gray-100 text-gray-800';
                                                if ($status == 'Hadir') {
                                                    $statusKeterlambatan = $laporan->status_keterlambatan ?? 'Tepat Waktu';
                                                    $status = $statusKeterlambatan == 'Terlambat' ? 'Terlambat' : 'Hadir';
                                                    $statusColor = $statusKeterlambatan == 'Terlambat' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800';
                                                } elseif ($status != 'Belum Absen') {
                                                    if ($status == 'Sakit') $statusColor = 'bg-yellow-100 text-yellow-800';
                                                    if ($status == 'Izin') $statusColor = 'bg-blue-100 text-blue-800';
                                                    if ($status == 'Alpa') $statusColor = 'bg-red-100 text-red-800';
                                                    if ($status == 'DL') $statusColor = 'bg-purple-100 text-purple-800';
                                                }
                                            @endphp
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ $status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            @if($jamKeSekarang)
                                                Tidak ada jadwal mengajar pada jam ini.
                                            @else
                                                Tidak ada jadwal (di luar jam pelajaran).
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
