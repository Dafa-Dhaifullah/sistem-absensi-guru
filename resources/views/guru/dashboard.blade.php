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
                                        $laporanAbsenText = '(' . $laporan->status_keterlambatan . ')';
                                    }

                                    $absenDibukaText = ''; // Inisialisasi
                                    if (!$laporan && !$bisaAbsen && !$sudahLewat) {
                                        $absenDibukaText = 'Absen dibuka pukul ' . $waktuBukaAbsen->format('H:i');
                                    }
                                @endphp

                                <!-- Kartu Jadwal Individual -->
                                <div class="border rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $bgColorClass }}">
                                    
                                    <!-- Info Jadwal (Kiri) -->
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-4">
                                            <div class="text-center w-24 flex-shrink-0">
                                                <div class="font-bold text-lg text-gray-800">
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
                                            
                                            @if ($statusDariPiket)
                                                <div class="text-xs text-red-600 mt-1">(Status Piket: {{ $laporan->status }})</div>
                                            @endif

                                         @elseif ($sudahLewat)
                                            <div class="text-sm font-bold text-gray-600">
                                                Absen Telah Ditutup
                                            </div>
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
                   {{-- 
                ======================================================================
                == [BARU] DAFTAR GURU PIKET HARI INI ==
                ======================================================================
                --}}
                <div class="bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Guru Piket Hari Ini
                            @if(isset($sesiSekarang))
                                <span class="text-sm font-normal text-gray-500 bg-gray-100 px-2 py-1 rounded-full ml-2">Sesi {{ $sesiSekarang }}</span>
                            @endif
                        </h2>

                        @if(isset($guruPiketHariIni) && count($guruPiketHariIni) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($guruPiketHariIni as $piket)
                                    @php
                                        // Deteksi & Normalisasi Nomor WA
                                        // Mencoba berbagai kemungkinan nama kolom di database (no_hp, telepon, phone)
                                        $noHp = $piket->no_wa ?? $piket->telepon ?? $piket->phone ?? ''; 
                                        $waUrl = '#';
                                        
                                        if($noHp) {
                                            // Hapus karakter non-digit
                                            $cleanHp = preg_replace('/[^0-9]/', '', $noHp);
                                            // Ganti 08 di depan dengan 628
                                            if(substr($cleanHp, 0, 1) == '0'){
                                                $cleanHp = '62' . substr($cleanHp, 1);
                                            }
                                            $waUrl = "https://wa.me/" . $cleanHp;
                                        }
                                    @endphp
                                    
                                    <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-lg hover:shadow-sm transition-shadow">
                                        <div class="flex items-center gap-3">
                                            {{-- Avatar / Inisial --}}
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                                {{ substr($piket->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-800 text-sm">{{ $piket->name }}</div>
                                                <div class="text-xs text-gray-500">Piket Pengawas</div>
                                            </div>
                                        </div>

                                        @if($noHp)
    <a href="{{ $waUrl }}" target="_blank"
        class="flex items-center gap-1 px-3 py-1.5 rounded-full bg-green-500 hover:bg-green-600 text-white text-xs font-medium transition-colors"
        title="Chat WhatsApp">

        {{-- Logo WhatsApp baru --}}
       <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.06 21.94L7.31 20.58C8.75 21.38 10.36 21.82 12.04 21.82C17.5 21.82 21.95 17.37 21.95 11.91C21.95 6.45 17.5 2 12.04 2M12.04 20.13C10.5 20.13 9 19.7 7.71 19.01L7.42 18.84L4.32 19.65L5.16 16.63L4.97 16.32C4.22 14.91 3.81 13.36 3.81 11.91C3.81 7.39 7.51 3.69 12.04 3.69C14.25 3.69 16.31 4.54 17.87 6.1C19.43 7.66 20.28 9.72 20.28 11.91C20.28 16.43 16.57 20.13 12.04 20.13M17.17 14.44C16.92 14.32 15.66 13.71 15.44 13.62C15.21 13.53 15.04 13.47 14.88 13.72C14.71 13.97 14.21 14.58 14.03 14.75C13.86 14.92 13.69 14.95 13.44 14.82C13.19 14.7 12.22 14.39 11.09 13.39C10.21 12.63 9.6 11.75 9.43 11.5C9.26 11.25 9.39 11.13 9.51 11.01C9.62 10.9 9.76 10.73 9.89 10.59C10.01 10.45 10.07 10.33 10.19 10.1C10.3 9.87 10.24 9.68 10.18 9.56C10.12 9.44 9.62 8.2 9.4 7.68C9.18 7.16 8.97 7.23 8.79 7.22C8.61 7.21 8.44 7.21 8.28 7.21C8.11 7.21 7.8 7.27 7.55 7.52C7.3 7.77 6.8 8.24 6.8 9.31C6.8 10.38 7.58 11.41 7.7 11.56C7.82 11.71 9.27 14.01 11.5 14.93C13.73 15.84 14.51 15.5 14.99 15.47C15.47 15.44 16.72 14.83 16.95 14.22C17.17 13.61 17.17 13.11 17.11 13C17.05 12.89 16.88 12.83 16.63 12.71Z"/>
                                            </svg>

        <span>Hubungi</span>

    </a>
@else
    <span class="text-xs text-gray-400 italic">No WA -</span>
@endif

                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">Tidak ada jadwal piket yang ditemukan untuk sesi ini.</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    
</x-teacher-layout>