<x-teacher-layout>
     <x-slot name="headerScripts">
        <meta http-equiv="refresh" content="60">
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Pemantauan Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Notifikasi Sukses & Error --}}
            @if (session('success'))
                <div class="p-4 rounded-lg bg-green-50 text-green-800 flex items-start gap-4 shadow-sm">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="p-4 rounded-lg bg-red-50 text-red-800 flex items-start gap-4 shadow-sm">
                     <div class="flex-shrink-0">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    </div>
                    <div>
                        <p class="font-bold text-sm">Gagal menyimpan data:</p>
                        <ul class="mt-1 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Kartu Petunjuk Tugas (Kolapsibel) --}}
            <div x-data="{ open: true }" class="bg-white p-6 rounded-xl shadow-sm">
                <button @click="open = !open" class="w-full flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Petunjuk Tugas Guru Piket</h3>
                    <svg class="w-6 h-6 text-gray-500 transform transition-transform" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div x-show="open" x-transition class="mt-6 border-t pt-6">
                    <p class="text-sm text-gray-600 mb-5">Ikuti langkah-langkah berikut untuk memastikan semua berjalan lancar.</p>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">1</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Pantau Status Kehadiran</h4>
                                <p class="text-sm text-gray-600">Lihat tabel di bawah untuk memantau status guru. Halaman ini akan memuat ulang data secara otomatis setiap 1 menit.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">2</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Hubungi Guru</h4>
                                <p class="text-sm text-gray-600">Jika ada guru berstatus "Belum Absen" mendekati atau lebih dari Jam Pertama masuknya, gunakan tombol WhatsApp untuk konfirmasi.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Gunakan Aksi Override</h4>
                                <p class="text-sm text-gray-600">Jika guru melapor (Sakit/Izin/DL) atau terkendala, ubah statusnya dan **wajib mengisi kolom keterangan**.</p>
                            </div>
                        </div>
                         <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">4</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Isi Logbook (Opsional)</h4>
                                <p class="text-sm text-gray-600">Catat kejadian penting dan tindak lanjutnya pada formulir logbook di bagian bawah.</p>
                            </div>
                        </div>
                         <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">5</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Simpan Perubahan</h4>
                                <p class="text-sm text-gray-600">Setelah selesai, klik tombol "Simpan Perubahan" di paling bawah untuk merekam semua aksi Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h4 class="font-semibold text-gray-800">Keterangan Warna Status</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-x-6 gap-y-3 mt-4 text-sm text-gray-600">
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-green-100 border mr-2"></div> Hadir</span>
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-orange-100 border mr-2"></div> Terlambat</span>
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-yellow-100 border mr-2"></div> Sakit</span>
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-blue-100 border mr-2"></div> Izin</span>
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-red-100 border mr-2"></div> Alpa</span>
                    <span class="inline-flex items-center"><div class="w-4 h-4 rounded-full bg-gray-200 border mr-2"></div> Belum Absen</span>
                </div>
            </div>

            {{-- Form Utama --}}
            <form action="{{ route('piket.laporan-harian.store') }}" method="POST">
                @csrf
                
                {{-- Kartu Tabel Kehadiran --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Daftar Kehadiran Guru Hari Ini</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    Hari: <span class="font-semibold text-indigo-600">{{ $hariIni ?? 'N/A' }}</span> | 
                                    Tipe Minggu: <span class="font-semibold text-indigo-600">{{ $tipeMinggu ?? 'N/A' }}</span>
                                </p>
                            </div>
                            <a href="{{ route('display.qr-kios') }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-slate-800 text-white text-xs font-semibold rounded-lg hover:bg-slate-700 transition duration-200">
                                <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 14.625a1.125 1.125 0 011.125-1.125h4.5a1.125 1.125 0 011.125 1.125v4.5a1.125 1.125 0 01-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5z" /></svg>
                                Tampilkan Kios QR
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Guru</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Pertama</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status Saat Ini</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi Override</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan (jika override)</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($guruWajibHadir as $guru)
                                    @php
                                        $laporan = $laporanHariIni->get($guru->id);
                                        $statusTersimpan = $laporan ? $laporan->status : null;
                                        $keteranganTersimpan = $laporan ? $laporan->keterangan_piket : null;
                                        $isHadir = $statusTersimpan == 'Hadir';

                                        $jadwalGuruIni = $semuaJadwalHariIni->where('user_id', $guru->id);
                                        $jamPertama = $jadwalGuruIni->min('jam_ke');
                                        $waktuMulai = $masterJamHariIni->get($jamPertama) ? \Carbon\Carbon::parse($masterJamHariIni->get($jamPertama)->jam_mulai)->format('H:i') : 'N/A';
                                        
                                        $statusText = 'Belum Absen';
                                        $statusColor = 'bg-gray-100 text-gray-800';
                                        if ($isHadir) {
                                            $statusText = ($laporan->status_keterlambatan == 'Terlambat') ? 'Terlambat' : 'Hadir';
                                            $statusColor = ($laporan->status_keterlambatan == 'Terlambat') ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800';
                                        } elseif ($statusTersimpan) {
                                            $statusText = $statusTersimpan;
                                            if ($statusTersimpan == 'Sakit') $statusColor = 'bg-yellow-100 text-yellow-800';
                                            if ($statusTersimpan == 'Izin') $statusColor = 'bg-blue-100 text-blue-800';
                                            if ($statusTersimpan == 'Alpa') $statusColor = 'bg-red-100 text-red-800';
                                            if ($statusTersimpan == 'DL') $statusColor = 'bg-purple-100 text-purple-800';
                                        }
                                    @endphp
                                    <tr class="odd:bg-white even:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $guru->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-semibold text-gray-700">
                                            @if ($jamPertama)
                                                Jam ke-{{ $jamPertama }} ({{ $waktuMulai }})
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <select name="status_guru[{{ $guru->id }}]" class="rounded-md border-gray-300 shadow-sm text-sm w-full disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed" {{ $isHadir ? 'disabled' : '' }}>
                                                <option value="" @if(!$statusTersimpan) selected @endif>-- Belum Diabsen --</option>
                                                <option value="Sakit" @if($statusTersimpan == 'Sakit') selected @endif>Sakit</option>
                                                <option value="Izin" @if($statusTersimpan == 'Izin') selected @endif>Izin</option>
                                                <option value="DL" @if($statusTersimpan == 'DL') selected @endif>Dinas Luar (DL)</option>
                                                <option value="Alpa" @if($statusTersimpan == 'Alpa') selected @endif>Alpa</option>
                                            </select>
                                            @if($isHadir)
                                                <p class="text-xs text-green-600 mt-1 italic">Sudah absen mandiri.</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <input type="text" 
                                                   name="keterangan_piket[{{ $guru->id }}]" 
                                                   value="{{ old('keterangan_piket.'.$guru->id, $keteranganTersimpan) }}"
                                                   placeholder="Catatan..."
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed"
                                                   {{ $isHadir ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            @if ($guru->no_wa)
                                                @php
                                                    $waNumber = preg_replace('/^0/', '62', $guru->no_wa);
                                                    $waLink = "https://wa.me/{$waNumber}?text=Assalamualaikum%20Bapak/Ibu%20{$guru->name},%20mohon%20konfirmasi%20kehadirannya%20hari%20ini.%20Terima%20kasih.";
                                                @endphp
                                                <a href="{{ $waLink }}" target="_blank" title="Hubungi via WhatsApp" class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition duration-200">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.149-.172.198-.296.297-.495.099-.198.05-.371-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01s-.521.074-.792.372c-.272.296-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.289.173-1.413z"/></svg>
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                            Tidak ada guru yang terjadwal untuk piket hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Kartu Logbook --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl mt-8">
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-800">Logbook Kejadian Harian (Opsional)</h3>
                        <p class="mt-1 text-sm text-gray-600">Catat kejadian penting atau kendala yang ditemui selama jam piket.</p>
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="kejadian_penting" :value="__('Kejadian Penting Hari Ini')" />
                                <textarea id="kejadian_penting" name="kejadian_penting" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('kejadian_penting') }}</textarea>
                            </div>
                            <div>
                                <x-input-label for="tindak_lanjut" :value="__('Tindak Lanjut Penyelesaiannya')" />
                                <textarea id="tindak_lanjut" name="tindak_lanjut" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('tindak_lanjut') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi Simpan --}}
                <div class="flex items-center justify-end mt-6">
                    <x-primary-button type="submit">
                        {{ __('Simpan Perubahan') }}
                    </x-primary-button>
                </div>
            </form>

        </div>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh halaman setiap 60 detik (1 menit)
            setTimeout(() => {
                window.location.reload();
            }, 60000);
        </script>
    @endpush
</x-teacher-layout>