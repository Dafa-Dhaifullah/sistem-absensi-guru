<x-admin-layout>
    <x-slot name="headerScripts">
        <meta http-equiv="refresh" content="60">
    </x-slot>

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
                        @elseif ($tipeMinggu == 'Hari Libur')
                             <p class="text-gray-700 font-semibold">Hari Ini Libur (Tidak ada jadwal pelajaran).</p>
                        @else
                            <p class="text-gray-700 font-semibold">Di luar jam pelajaran.</p>
                        @endif
                    </div>

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guru Pengajar</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status Kehadiran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bukti Foto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($jadwalSekarang as $jadwal)
                                    @php
                                        // Cari laporan yang sesuai dengan ID jadwal ini
                                        $laporan = $laporanHariIni->get($jadwal->id);
                                        $statusText = 'Belum Absen';
                                        $statusColor = 'bg-gray-100 text-gray-800';
                                        
                                        if ($laporan) {
                                            if ($laporan->status == 'Hadir') {
                                                $statusText = $laporan->status_keterlambatan == 'Terlambat' ? 'Terlambat' : 'Hadir';
                                                $statusColor = $laporan->status_keterlambatan == 'Terlambat' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800';
                                            } else {
                                                $statusText = $laporan->status;
                                                if ($laporan->status == 'Sakit') $statusColor = 'bg-yellow-100 text-yellow-800';
                                                if ($laporan->status == 'Izin') $statusColor = 'bg-blue-100 text-blue-800';
                                                if ($laporan->status == 'Alpa') $statusColor = 'bg-red-100 text-red-800';
                                                if ($laporan->status == 'DL') $statusColor = 'bg-purple-100 text-purple-800';
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $jadwal->kelas }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->user->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">
                                            {{ $laporan->keterangan_piket ?? '' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @if ($laporan && $laporan->foto_selfie_path)
                                                <a href="{{ Illuminate\Support\Facades\Storage::url($laporan->foto_selfie_path) }}" target="_blank" class="text-blue-600 hover:underline">
                                                    Lihat
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                            @if($jamKeSekarang)
                                                Tidak ada jadwal mengajar pada jam ini.
                                            @else
                                                Tidak ada jadwal (di luar jam pelajaran atau hari libur).
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