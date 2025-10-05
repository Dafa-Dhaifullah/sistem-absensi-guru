<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Pemantauan Piket') }}
        </h2>
    </x-slot>
    <div class="p-6 text-gray-900">
     <a href="{{ route('display.qr-kios') }}" target="_blank" 
                           class="block w-full p-6 bg-gray-700 text-white rounded-lg shadow-md hover:bg-gray-800 transition duration-150">
                            <h3 class="text-2xl font-bold">Tampilkan QR Code Absensi</h3>
                            <p class="text-gray-300">Buka halaman ini di monitor/tablet untuk absensi.</p>
                        </a>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                    <p class="font-bold">Gagal menyimpan:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('piket.laporan-harian.store') }}" method="POST">
                @csrf
                
                <!-- KARTU DAFTAR GURU -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            Daftar Kehadiran Guru Hari Ini
                        </h3>
                        <p class="text-sm text-gray-600">
                            Hari: <span class="font-semibold">{{ $hariIni ?? 'N/A' }}</span> | 
                            Tipe Minggu: <span class="font-semibold">{{ $tipeMinggu ?? 'N/A' }}</span>
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Guru</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status Saat Ini</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi Override</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bukti Foto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($guruWajibHadir as $guru)
                                    @php
                                        $laporan = $laporanHariIni->get($guru->id);
                                        $statusTersimpan = $laporan ? $laporan->status : null;
                                        $statusKeterlambatan = $laporan ? $laporan->status_keterlambatan : null;

                                        $statusText = 'Belum Absen';
                                        $statusColor = 'bg-gray-100 text-gray-800';
                                        if ($statusTersimpan == 'Hadir') {
                                            $statusText = $statusKeterlambatan == 'Terlambat' ? 'Terlambat' : 'Hadir';
                                            $statusColor = $statusKeterlambatan == 'Terlambat' ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800';
                                        } elseif ($statusTersimpan) {
                                            $statusText = $statusTersimpan;
                                            if ($statusTersimpan == 'Sakit') $statusColor = 'bg-yellow-100 text-yellow-800';
                                            if ($statusTersimpan == 'Izin') $statusColor = 'bg-blue-100 text-blue-800';
                                            if ($statusTersimpan == 'Alpa') $statusColor = 'bg-red-100 text-red-800';
                                            if ($statusTersimpan == 'DL') $statusColor = 'bg-purple-100 text-purple-800';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $guru->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <select name="status_guru[{{ $guru->id }}]" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
                                                <option value="" @if(!$statusTersimpan) selected @endif>-- Belum Diabsen --</option>
                                                <!-- Opsi 'Hadir' SENGAJA DIHILANGKAN -->
                                                <option value="Sakit" @if($statusTersimpan == 'Sakit') selected @endif>Sakit</option>
                                                <option value="Izin" @if($statusTersimpan == 'Izin') selected @endif>Izin</option>
                                                <option value="DL" @if($statusTersimpan == 'DL') selected @endif>Dinas Luar (DL)</option>
                                                <option value="Alpa" @if($statusTersimpan == 'Alpa') selected @endif>Alpa</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @if ($guru->no_wa)
                                                @php
                                                    $waNumber = preg_replace('/^0/', '62', $guru->no_wa);
                                                    $waLink = "https://wa.me/{$waNumber}?text=Assalamualaikum%20Bapak/Ibu%20{$guru->name},%20mohon%20konfirmasi%20kehadirannya%20hari%20ini.%20Terima%20kasih.";
                                                @endphp
                                                <a href="{{ $waLink }}" target="_blank" title="Hubungi via WhatsApp"
                                                   class="inline-flex items-center justify-center w-8 h-8 bg-green-500 text-white rounded-full hover:bg-green-600 transition">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.149-.172.198-.296.297-.495.099-.198.05-.371-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01s-.521.074-.792.372c-.272.296-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.289.173-1.413z"/></svg>
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
            @if ($laporan && $laporan->foto_selfie_path)
                <a href="{{ Storage::url($laporan->foto_selfie_path) }}" target="_blank"
                   class="text-blue-600 hover:underline">
                    Lihat
                </a>
            @else
                -
            @endif
        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada guru yang terjadwal hari ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KARTU LOGBOOK -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Logbook Kejadian Harian (Opsional)</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="kejadian_penting" :value="__('Kejadian Penting Hari Ini')" />
                                <textarea id="kejadian_penting" name="kejadian_penting" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('kejadian_penting') }}</textarea>
                            </div>
                            <div>
                                <x-input-label for="tindak_lanjut" :value="__('Tindak Lanjut Penyelesaiannya')" />
                                <textarea id="tindak_lanjut" name="tindak_lanjut" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('tindak_lanjut') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="flex items-center justify-end mt-6">
                    <x-primary-button type="submit">
                        {{ __('Simpan Perubahan') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-teacher-layout>

