<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Keterlambatan Hari Ini') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">
                        Daftar sesi pelajaran di mana guru melakukan absensi mandiri melewati batas toleransi keterlambatan (15 menit).
                    </p>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Guru</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal Sesi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Absen</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bukti Foto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($laporanTerlambat as $laporan)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $laporan->user->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            Jam ke-{{ $laporan->jadwalPelajaran->jam_ke ?? '?' }}
                                            ({{ $laporan->jadwalPelajaran->kelas ?? 'N/A' }})
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600">
                                            {{ \Carbon\Carbon::parse($laporan->jam_absen)->format('H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @if ($laporan->foto_selfie_path)
                                                <a href="{{ Illuminate\Support\Facades\Storage::url($laporan->foto_selfie_path) }}" target="_blank" class="text-blue-600 hover:underline">
                                                    Lihat Foto
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada guru yang terlambat hari ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>