<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <!-- 
        ==========================================================
        == REVISI 1: Logika Alpine.js dipindahkan ke sini ==
        ==========================================================
    -->
    <div x-data="{
        modalOpen: false,
        selectedJadwalIds: [],
        openModal(jadwalIds) {
            this.selectedJadwalIds = jadwalIds;
            this.modalOpen = true;
            // Tampilkan kembali scanner dan sembunyikan selfie section
            document.getElementById('qr-scanner-section').classList.remove('hidden');
            document.getElementById('selfie-section').classList.add('hidden');
            // Beri jeda sedikit agar modal tampil, baru mulai scanner
            setTimeout(() => startScanner(), 150);
        }
    }">

        <!-- Modal untuk Scan QR + Selfie -->
        <div @keydown.escape.window="modalOpen = false" x-show="modalOpen" 
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            <div @click.away="modalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
                <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <template x-for="jadwalId in selectedJadwalIds" :key="jadwalId">
                        <input type="hidden" name="jadwal_ids[]" :value="jadwalId">
                    </template>
                    <input type="hidden" name="qr_token" id="qr_token">
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Form Absensi</h3>
                        <div id="qr-scanner-section" class="mt-4 flex flex-col items-center">
                            <p class="text-sm text-gray-600 mb-2">Arahkan kamera ke QR Code di kelas.</p>
                            <div id="qr-reader" class="w-full max-w-sm aspect-square border-2 border-dashed rounded-lg"></div>
                            <p id="qr-reader-status" class="text-sm text-gray-500 mt-2 text-center h-5"></p>
                        </div>
                        <div id="selfie-section" class="hidden mt-4">
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
                                <p class="font-semibold text-blue-800">✔️ QR Code berhasil dipindai!</p>
                                <p class="text-sm text-blue-700">Langkah terakhir, silakan ambil foto selfie Anda.</p>
                            </div>
                            <div class="mt-6 max-w-md mx-auto">
                                <x-input-label for="foto_selfie" :value="__('Ambil Foto Selfie')" class="mb-2"/>
                                <input id="foto_selfie" name="foto_selfie" type="file" accept="image/*" capture="user" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition duration-150" required>
                                <x-input-error :messages="$errors->get('foto_selfie')" class="mt-2" />
                            </div>
                            <div class="mt-6 text-center">
                                <x-primary-button type="submit">{{ __('Kirim Absensi') }}</x-primary-button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                {{-- Notifikasi --}}
                @if (session('success'))
                    <x-notification type="success" :message="session('success')" />
                @endif
                @if ($errors->any())
                    <x-notification type="error" :message="$errors->first()" />
                @endif
                
                {{-- Tombol Pindah Dasbor --}}
                @if($sedangPiket)
                    <a href="{{ route('piket.dashboard') }}" class="block w-full p-4 bg-gray-700 text-white rounded-lg shadow-md hover:bg-gray-800 transition text-center">
                        <h3 class="text-xl font-bold">Buka Dasbor Pemantauan Piket</h3>
                    </a>
                @endif

                {{-- Kartu Jadwal Hari Ini --}}
                <div class="bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">Jadwal Mengajar Anda Hari Ini</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Hari: <span class="font-semibold">{{ $hariIni }}</span> | Tipe Minggu: <span class="font-semibold">{{ $tipeMinggu }}</span>
                        </p>
                        <div class="mt-4 space-y-4">
                            @forelse ($jadwalBlok as $blok)
                                @php
                                    $jamMulai = $masterJamHariIni->get($blok['jam_pertama']);
                                    $jamSelesai = $masterJamHariIni->get($blok['jam_terakhir']);
                                    
                                    $waktuMulai = \Carbon\Carbon::parse($jamMulai->jam_mulai);
                                    $waktuSelesai = \Carbon\Carbon::parse($jamSelesai->jam_selesai);
                                    $waktuBukaAbsen = $waktuMulai->copy()->subMinutes(15);
                                    $sekarang = now('Asia/Jakarta');
                                    
                                    $bisaAbsen = $sekarang->between($waktuBukaAbsen, $waktuSelesai);
                                    $sudahLewat = $sekarang->isAfter($waktuSelesai);
                                    
                                    $laporan = $laporanHariIni->get($blok['jadwal_ids'][0]);
                                @endphp

                                <div class="border rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 
                                    {{ $laporan ? 'bg-green-50 border-green-200' : ($bisaAbsen ? 'bg-blue-50 border-blue-200' : ($sudahLewat ? 'bg-red-50 border-red-200' : 'bg-gray-50')) }}">
                                    
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-4">
                                            <div class="text-center w-24 flex-shrink-0">
                                                <div class="font-bold text-lg text-gray-800">
                                                    Jam {{ $blok['jam_pertama'] }}{{ $blok['jam_pertama'] != $blok['jam_terakhir'] ? '-' . $blok['jam_terakhir'] : '' }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $waktuMulai->format('H:i') }} - {{ $waktuSelesai->format('H:i') }}</div>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $blok['kelas'] }}</div>
                                                <div class="text-sm text-gray-600">{{ $blok['mata_pelajaran'] ?? 'Tanpa Mapel' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-shrink-0 w-full md:w-auto text-center">
                                        @if ($laporan)
                                            <div class="text-sm">
                                                <span class="font-bold text-green-700">Sudah Absen</span>
                                                <div class="text-xs text-gray-500">({{ $laporan->status_keterlambatan }} pada {{ \Carbon\Carbon::parse($laporan->jam_absen)->format('H:i') }})</div>
                                            </div>
                                        @elseif ($bisaAbsen)
                                            <!-- ========================================================== -->
                                            <!-- == REVISI 2: Panggil fungsi openModal() == -->
                                            <!-- ========================================================== -->
                                            <button @click="openModal({{ json_encode($blok['jadwal_ids']) }})" class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 text-sm">
                                                Absen Masuk Kelas
                                            </button>
                                        @elseif ($sudahLewat)
                                            <div class="text-sm font-bold text-red-600">
                                                Absen Telah Ditutup
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500">
                                                Absen dibuka pukul {{ $waktuBukaAbsen->format('H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    Tidak ada jadwal mengajar hari ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ... Sisa kode (Kartu Info Piket) ... --}}
            </div>
        </div>
    </div>
    
    <!-- ========================================================== -->
    <!-- == REVISI 3: Sederhanakan Skrip == -->
    <!-- ========================================================== -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        // Letakkan variabel scanner di scope global agar bisa diakses
        let html5QrcodeScanner;

        function startScanner() {
            // Hentikan scanner lama jika ada untuk mencegah error
            if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
                html5QrcodeScanner.clear();
            }
            
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { fps: 10, qrbox: {width: 250, height: 250} }, 
                false
            );
            
            const statusElement = document.getElementById('qr-reader-status');
            const selfieSection = document.getElementById('selfie-section');
            const qrTokenInput = document.getElementById('qr_token');

            function onScanSuccess(decodedText, decodedResult) {
                html5QrcodeScanner.clear();
                qrTokenInput.value = decodedText;
                document.getElementById('qr-scanner-section').classList.add('hidden');
                selfieSection.classList.remove('hidden');
            }

            function onScanFailure(error) {
                statusElement.textContent = 'Arahkan kamera ke QR Code...';
            }
            
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    </script>
</x-teacher-layout>

