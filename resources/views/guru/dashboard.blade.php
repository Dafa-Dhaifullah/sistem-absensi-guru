<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <!-- 
    ======================================================================
    == PUSAT KONTROL ALPINE.JS (x-data) ==
    ======================================================================
    Mengelola semua state: modal, step (qr/selfie), scanner, dan file.
    -->
    <div x-data="{
        modalOpen: false,
        step: 'qr', // State untuk melacak langkah: 'qr' atau 'selfie'
        selectedJadwalIds: [],
        qrToken: '',
        scannerInstance: null,
        fileName: '', // State untuk melacak nama file selfie

        // Fungsi untuk membuka modal
        openModal(jadwalIds) {
            this.selectedJadwalIds = jadwalIds;
            this.step = 'qr'; // Selalu reset ke langkah 'qr'
            this.qrToken = '';
            this.fileName = ''; // Reset nama file
            this.modalOpen = true;
            // x-effect akan menangani start scanner
        },

        // Fungsi untuk menutup modal
        closeModal() {
            this.modalOpen = false;
            // x-effect akan menangani stop scanner
        },

        // Fungsi untuk mengupdate nama file
        updateFileName(event) {
            if (event.target.files.length > 0) {
                this.fileName = event.target.files[0].name;
            } else {
                this.fileName = '';
            }
        },

        // Fungsi untuk menginisialisasi dan memulai scanner
        initScanner() {
            if (this.scannerInstance) {
                try { this.scannerInstance.clear(); } catch (e) {}
            }
            
            const alpineComponent = this;

            const onScanSuccess = (decodedText, decodedResult) => {
                alpineComponent.qrToken = decodedText;
                alpineComponent.step = 'selfie'; // Ganti state ke 'selfie'
                
                if (alpineComponent.scannerInstance) {
                    try { alpineComponent.scannerInstance.clear(); } catch (e) {}
                    alpineComponent.scannerInstance = null;
                }
            };

            const onScanFailure = (error) => {
                const statusEl = document.getElementById('qr-reader-status');
                if (statusEl) {
                    statusEl.textContent = 'Arahkan kamera ke QR Code...';
                }
            };

            try {
                this.scannerInstance = new Html5QrcodeScanner(
                    'qr-reader', 
                    { fps: 10, qrbox: {width: 250, height: 250} }, 
                    false
                );
                this.scannerInstance.render(onScanSuccess, onScanFailure);
            } catch (e) {
                console.error('Gagal memulai scanner:', e);
                const statusEl = document.getElementById('qr-reader-status');
                if (statusEl) {
                    statusEl.textContent = 'Gagal memuat kamera. Segarkan halaman.';
                }
            }
        },

        // Fungsi untuk mengelola siklus hidup scanner (otomatis)
        manageScannerLifecycle() {
            if (this.modalOpen && this.step === 'qr') {
                // Modal terbuka di langkah 'qr', mulai scanner
                this.$nextTick(() => {
                    if (document.getElementById('qr-reader')) {
                        this.initScanner();
                    }
                });
            } else if ((!this.modalOpen || this.step !== 'qr') && this.scannerInstance) {
                // Modal tertutup ATAU pindah step, hentikan scanner
                try { 
                    this.scannerInstance.clear(); 
                    this.scannerInstance = null;
                } catch (e) {}
            }
        }
    }">

        <!-- 
        ======================================================================
        == MODAL ABSENSI (QR + SELFIE) ==
        ======================================================================
        Dikelola oleh state 'modalOpen' dan 'step'.
        -->
        <div @keydown.escape.window="closeModal()" x-show="modalOpen" 
             x-effect="manageScannerLifecycle()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            
            <!-- Klik @click.away akan menutup modal -->
            <div @click.away="closeModal()" class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
                <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Input untuk jadwal_ids (tidak berubah) -->
                    <template x-for="jadwalId in selectedJadwalIds" :key="jadwalId">
                        <input type="hidden" name="jadwal_ids[]" :value="jadwalId">
                    </template>
                    
                    <!-- Input token QR (terikat ke state) -->
                    <input type="hidden" name="qr_token" id="qr_token" x-model="qrToken">
                    
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Form Absensi</h3>
                        
                        <!-- 
                        == LANGKAH 1: SCANNER QR ==
                        Tampil HANYA jika state step = 'qr'
                        -->
                        <div id="qr-scanner-section" x-show="step === 'qr'" class="mt-4 flex flex-col items-center">
                            <p class="text-sm text-gray-600 mb-2">Arahkan kamera ke QR Code di kelas.</p>
                            <div id="qr-reader" class="w-full max-w-sm aspect-square border-2 border-dashed rounded-lg"></div>
                            <p id="qr-reader-status" class="text-sm text-gray-500 mt-2 text-center h-5"></p>
                        </div>

                        <!-- 
                        == LANGKAH 2: AMBIL SELFIE ==
                        Tampil HANYA jika state step = 'selfie'
                        -->
                        <div id="selfie-section" x-show="step === 'selfie'" class="mt-4">
                            <!-- Pesan Sukses -->
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
                                <p class="font-semibold text-blue-800">✔️ QR Code berhasil dipindai!</p>
                                <p class="text-sm text-blue-700">Langkah terakhir, silakan ambil foto selfie Anda.</p>
                            </div>

                            <!-- Tombol Ambil Foto Kustom -->
                            <div class="mt-6 max-w-md mx-auto">
                                <x-input-label for="foto_selfie" :value="__('Ambil Foto Selfie')" class="mb-2"/>

                                <!-- Input file asli (disembunyikan) -->
                                <input id="foto_selfie" name="foto_selfie" type="file" accept="image/*" capture="user" required
                                       x-ref="fileInput" 
                                       @change="updateFileName($event)"
                                       style="display: none;">

                                <!-- Tombol Kustom & Teks Status -->
                                <div class="flex items-center space-x-3">
                                    <!-- Tombol yang dilihat pengguna -->
                                    <x-secondary-button type="button" @click.prevent="$refs.fileInput.click()">
                                        <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 6a2 2 0 012-2h1.172a2 2 0 011.414.586l.828.828A2 2 0 008.828 6H12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            <path d="M15 8a1 1 0 10-2 0v2a1 1 0 102 0V8zM3 10a1 1 0 011-1h2a1 1 0 110 2H4a1 1 0 01-1-1z" />
                                        </svg>
                                        Ambil Foto
                                    </x-secondary-button>

                                    <!-- Teks status nama file -->
                                    <span
          x-text="fileName || 'Belum ada foto.'"
          class="text-sm text-gray-500 truncate"
          :class="fileName ? 'text-green-600 font-semibold' : null">
        </span>
                                </div>
                                
                                <x-input-error :messages="$errors->get('foto_selfie')" class="mt-2" />
                            </div>
                            
                            
                            <div class="mt-6 text-center">
                                
                                <x-primary-button
  type="submit"
  x-bind:disabled="!fileName"
  x-bind:class="!fileName ? 'opacity-50 cursor-not-allowed' : ''">
  {{ __('Kirim Absensi') }}
</x-primary-button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 
        ======================================================================
        == KONTEN UTAMA HALAMAN (JADWAL) ==
        ======================================================================
        -->
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

                                    // Cek apakah guru sudah absen 'Hadir' secara mandiri (selfie)
                                    // Kita butuh auth()->id() untuk perbandingan
                                    $userId = auth()->id(); 
                                    $sudahAbsenMandiri = $laporan && $laporan->status == 'Hadir' && $laporan->diabsen_oleh == $userId;
                                    
                                    // Cek apakah ada status tapi BUKAN absen mandiri (cth: Alpa/Sakit dari Piket)
                                    $statusDariPiket = $laporan && !$sudahAbsenMandiri;

                                    
                                    $bgColorClass = 'bg-gray-50'; // Default
                                    if ($laporan) {
                                        $bgColorClass = 'bg-green-50 border-green-200';
                                    } elseif ($bisaAbsen) {
                                        $bgColorClass = 'bg-blue-50 border-blue-200';
                                    } elseif ($sudahLewat) {
                                        $bgColorClass = 'bg-red-50 border-red-200';
                                    }

                                    
                                    $jamText = 'Jam ' . $blok['jam_pertama'];
                                    if ($blok['jam_pertama'] != $blok['jam_terakhir']) {
                                        $jamText .= '-' . $blok['jam_terakhir'];
                                    }

                                    
                                    $laporanAbsenText = ''; // Inisialisasi
                                    if ($laporan && $laporan->jam_absen) {
                                        $laporanAbsenText = '(' . $laporan->status_keterlambatan . ' pada ' . \Carbon\Carbon::parse($laporan->jam_absen)->format('H:i') . ')';
                                    } elseif ($laporan) {
                                        // Fallback jika jam absen null tapi laporan ada
                                        $laporanAbsenText = '(' . $laporan->status_keterlambatan . ')';
                                    }

                          
                                    $absenDibukaText = ''; // Inisialisasi
                                    if (!$laporan && !$bisaAbsen && !$sudahLewat) {
                                        $absenDibukaText = 'Absen dibuka pukul ' . $waktuBukaAbsen->format('H:i');
                                    }
                                @endphp

                                <!-- Kartu Jadwal Individual -->
                                <!-- Cetak $bgColorClass yang sudah bersih di sini -->
                                <div class="border rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $bgColorClass }}">
                                    
                                    <!-- Info Jadwal (Kiri) -->
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-4">
                                            <div class="text-center w-24 flex-shrink-0">
                                                <div class="font-bold text-lg text-gray-800">
                                                    <!-- Cetak $jamText yang sudah bersih di sini -->
                                                    {{ $jamText }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $waktuMulai->format('H:i') }} - {{ $waktuSelesai->format('H:i') }}</div>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $blok['kelas'] }}</div>
                                                <div class="text-sm text-gray-600">{{ $blok['mata_pelajaran'] ?? 'Tanpa Mapel' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status/Tombol Absen (Kanan) -->
                                    <div class="flex-shrink-0 w-full md:w-auto text-center">
                                         @if ($sudahAbsenMandiri)
                                            <div class="text-sm">
                                                <span class="font-bold text-green-700">Sudah Absen</span>
                                                <div class="text-xs text-gray-500">{{ $laporanAbsenText }}</div>
                                            </div>
                                        
                                        @elseif ($bisaAbsen)
                                            
                                            <button type="button" @click="openModal(@json($blok['jadwal_ids']))" class="px-4 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 text-sm">
                                                Absen Masuk Kelas
                                            </button>
                                            
                                            {{-- Tampilkan status dari piket JIKA ada --}}
                                            @if ($statusDariPiket)
                                                <div class="text-xs text-red-600 mt-1">(Status Piket: {{ $laporan->status }})</div>
                                            @endif

                                        @elseif ($sudahLewat)
                                            <div class="text-sm font-bold text-gray-600">
                                                Absen Telah Ditutup
                                            </div>
                                            {{-- Tampilkan status dari piket JIKA ada --}}
                                            @if ($statusDariPiket)
                                                <div class="text-xs text-red-600 mt-1">(Status Piket: {{ $laporan->status }})</div>
                                            @endif
                                            
                                        @else
                                            <div class="text-sm text-gray-500">
                                                {{ $absenDibukaText }}
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
                <!-- Akhir Kartu Jadwal -->

            </div>
        </div>
    </div>
    
    <!-- Impor library scanner (Tetap dibutuhkan) -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
    <!-- 
      Tidak ada lagi <script> global. 
      Semua logika sudah ada di dalam x-data di atas.
    -->
    
</x-teacher-layout>

