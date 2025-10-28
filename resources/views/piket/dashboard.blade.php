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
            <a href="{{ route('guru.dashboard') }}"
           class="block w-full p-4 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 text-center">
            <h3 class="text-xl font-bold">Buka Dasbor Absensi Pribadi</h3>
            <p class="text-indigo-100 text-sm">Klik untuk melakukan absensi mandiri (Scan QR + Selfie).</p>
        </a>
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
                                <p class="text-sm text-gray-600">Jika ada guru berstatus "Belum Absen" mendekati atau lebih dari Jam masuknya, gunakan tombol WhatsApp untuk konfirmasi.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Gunakan Aksi Override</h4>
                                <p class="text-sm text-gray-600">Jika guru melapor (Sakit/Izin/DL) atau terkendala, ubah statusnya dan <strong>wajib mengisi kolom keterangan</strong>.</p>
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
          <form x-data x-ref="formPiket" action="{{ route('piket.laporan-harian.store') }}" method="POST">
                @csrf
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Kehadiran Guru Hari Ini</h3>
                        <p class="text-sm text-gray-600">
                            Hari: <span class="font-semibold">{{ $hariIni ?? 'N/A' }}</span> | 
                            Tipe Minggu: <span class="font-semibold">{{ $tipeMinggu ?? 'N/A' }}</span>
                        </p>
                    </div>

                    <!-- DAFTAR GURU -->
                    <div class="divide-y divide-gray-200">
                        @forelse ($guruWajibHadir as $guru)
                            @php
                                // Logika untuk mengelompokkan jadwal guru ini menjadi blok
                                $jadwalGuruIni = $semuaJadwalHariIni->where('user_id', $guru->id);
                                $jadwalBlok = collect();
                                $tempBlock = null;
                                foreach ($jadwalGuruIni as $jadwal) {
                                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                                        $tempBlock['jadwal_ids'][] = $jadwal->id;
                                        $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                                    } else {
                                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                                        $tempBlock = [
                                            'jadwal_ids' => [$jadwal->id],
                                            'jam_pertama' => $jadwal->jam_ke,
                                            'jam_terakhir' => $jadwal->jam_ke,
                                            'kelas' => $jadwal->kelas,
                                        ];
                                    }
                                }
                                if ($tempBlock) $jadwalBlok->push($tempBlock);
                            @endphp

                            <div class="p-6 hover:bg-gray-50">
                                <!-- Info Utama Guru -->
                                <div class="flex justify-between items-center">
                                    <h4 class="text-base font-semibold text-gray-800">{{ $guru->name }}</h4>
                                    @if ($guru->no_wa)
                                        @php
                                            $waNumber = preg_replace('/^0/', '62', $guru->no_wa);
                                            $waLink = "https://wa.me/{$waNumber}?text=Assalamualaikum%20Bapak/Ibu%20{$guru->name},%20mohon%20konfirmasi%20kehadirannya%20untuk%20jadwal%20hari%20ini.%20Terima%20kasih.";
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" class="inline-flex items-center text-xs text-green-600 font-semibold hover:text-green-700">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24...z"/></svg>
                                            Hubungi
                                        </a>
                                    @endif
                                </div>
                                <!-- Detail Jadwal Guru per Blok -->
                                <div class="mt-4 space-y-3">
                                    @foreach ($jadwalBlok as $blok)
                                        @php
                                            $laporan = $laporanHariIni->get($blok['jadwal_ids'][0]); // Cek status dari jam pertama blok
                                            $isHadir = $laporan && $laporan->status == 'Hadir';
                                            
                                            // Logika Badge Status
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
                                        <div class="grid grid-cols-12 gap-4 items-center">
                                            <div class="col-span-12 md:col-span-4 text-sm text-gray-600">
                                                Jam {{ $blok['jam_pertama'] }}{{ $blok['jam_pertama'] != $blok['jam_terakhir'] ? '-' . $blok['jam_terakhir'] : '' }} 
                                                <span class="font-semibold text-gray-800">({{ $blok['kelas'] }})</span>
                                            </div>
                                            <div class="col-span-12 md:col-span-2 text-center">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                    {{ $statusText }}
                                                </span>
                                            </div>
                                            <div class="col-span-12 md:col-span-6">
                                                <div class="flex items-center gap-2">
                                                    <!-- Kirim semua ID jadwal dalam blok -->
                                                    @foreach($blok['jadwal_ids'] as $jadwalId)
                                                        <input type="hidden" name="jadwal_ids_override[{{ $jadwalId }}]" value="1">
                                                    @endforeach
                                                    <select name="status_override[{{ $blok['jadwal_ids'][0] }}]" class="w-full rounded-md text-sm ..." {{ $isHadir ? 'disabled' : '' }}>
                                                        <option value="">-- Override --</option>
                                                        <option value="Sakit">Sakit</option>
                                                        <option value="Izin">Izin</option>
                                                        <option value="Alpa">Alpa</option>
                                                        <option value="DL">Dinas Luar</option>
                                                    </select>
                                                    <input type="text" name="keterangan_piket[{{ $blok['jadwal_ids'][0] }}]" placeholder="Catatan..." class="w-full text-sm ..." {{ $isHadir ? 'disabled' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-gray-500">
                                Tidak ada guru yang terjadwal hari ini.
                            </div>
                        @endforelse
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
                    <x-primary-button type="button" @click.prevent="
                        Swal.fire({
                            title: 'Simpan Perubahan?',
                            text: 'Status kehadiran guru akan diperbarui.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, simpan!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $refs.formPiket.submit();
                            }
                        })
                    ">
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