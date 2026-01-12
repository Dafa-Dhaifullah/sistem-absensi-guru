<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <!-- 
    ======================================================================
    == PUSAT KONTROL ALPINE.JS (LIVE CAMERA) ==
    ======================================================================
    -->
    <div x-data="{
        modalOpen: false,
        step: 'qr', 
        selectedJadwalIds: [],
        qrToken: '',
        scannerInstance: null,
        photoData: null, // Base64 String
        stream: null, // Camera Stream

        openModal(jadwalIds) {
            this.selectedJadwalIds = jadwalIds;
            this.step = 'qr';
            this.qrToken = '';
            this.photoData = null;
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.stopCamera();
            if (this.scannerInstance) {
                try { this.scannerInstance.clear(); } catch (e) {}
            }
        },

        initScanner() {
            if (this.scannerInstance) {
                try { this.scannerInstance.clear(); } catch (e) {}
            }
            const alpineComponent = this;
            const onScanSuccess = (decodedText, decodedResult) => {
                alpineComponent.qrToken = decodedText;
                alpineComponent.step = 'selfie';
                
                if (alpineComponent.scannerInstance) {
                    try { alpineComponent.scannerInstance.clear(); } catch (e) {}
                    alpineComponent.scannerInstance = null;
                }
                
                // Jeda sebentar sebelum menyalakan kamera
                setTimeout(() => {
                    alpineComponent.startCamera();
                }, 500);
            };

            const onScanFailure = (error) => {};

            try {
                this.scannerInstance = new Html5QrcodeScanner(
                    'qr-reader', 
                    { fps: 10, qrbox: {width: 250, height: 250} }, 
                    false
                );
                this.scannerInstance.render(onScanSuccess, onScanFailure);
            } catch (e) {
                console.error('Error Scanner', e);
            }
        },

        // === LOGIKA KAMERA (WEB STREAM) ===
        startCamera() {
            const video = this.$refs.video;
            // Akses kamera depan/user
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                    .then(stream => {
                        this.stream = stream;
                        video.srcObject = stream;
                        video.play();
                    })
                    .catch(err => {
                        console.error('Gagal akses kamera:', err);
                        alert('Kamera tidak dapat diakses. Pastikan izin diberikan di browser.');
                    });
            } else {
                alert('Browser Anda tidak mendukung akses kamera langsung.');
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },

        takeSnapshot() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            
            if (video.videoWidth && video.videoHeight) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                this.photoData = canvas.toDataURL('image/jpeg', 0.8); // Kompresi 0.8
                this.stopCamera();
            }
        },

        retakePhoto() {
            this.photoData = null;
            this.startCamera();
        },

        manageScannerLifecycle() {
            if (this.modalOpen && this.step === 'qr') {
                this.$nextTick(() => {
                    if (document.getElementById('qr-reader')) {
                        this.initScanner();
                    }
                });
            }
        }
    }">

        <!-- MODAL -->
        <div @keydown.escape.window="closeModal()" x-show="modalOpen" 
             x-effect="manageScannerLifecycle()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
            
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                <!-- FORM -->
                <!-- Perhatikan: Tidak ada input type='file' di sini -->
                <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST">
                    @csrf
                    
                    <template x-for="jadwalId in selectedJadwalIds" :key="jadwalId">
                        <input type="hidden" name="jadwal_ids[]" :value="jadwalId">
                    </template>
                    <input type="hidden" name="qr_token" x-model="qrToken">
                    
                    <!-- HANYA MENERIMA TEXT BASE64 (BUKAN FILE) -->
                    <input type="hidden" name="foto_selfie_base64" x-model="photoData">

                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 text-center mb-4">
                            <span x-show="step === 'qr'">Scan QR Code Kelas</span>
                            <span x-show="step === 'selfie'">Ambil Foto Selfie</span>
                        </h3>
                        
                        <!-- STEP 1: QR SCANNER -->
                        <div x-show="step === 'qr'" class="flex flex-col items-center">
                            <div id="qr-reader" class="w-full max-w-sm aspect-square border-2 border-dashed rounded-lg bg-gray-100"></div>
                            <p class="text-sm text-gray-500 mt-2">Arahkan kamera ke QR Code di dinding.</p>
                        </div>

                        <!-- STEP 2: LIVE CAMERA -->
                        <div x-show="step === 'selfie'" class="flex flex-col items-center">
                            
                            <!-- Video Live -->
                            <div x-show="!photoData" class="relative w-full max-w-sm bg-black rounded-lg overflow-hidden aspect-[3/4] shadow-inner">
                                <video x-ref="video" class="w-full h-full object-cover transform scale-x-[-1]" autoplay playsinline muted></video>
                                <div class="absolute bottom-4 left-0 right-0 text-center text-white text-xs bg-black bg-opacity-50 py-1">
                                    Live Camera
                                </div>
                            </div>

                            <!-- Preview Foto -->
                            <div x-show="photoData" class="relative w-full max-w-sm rounded-lg overflow-hidden aspect-[3/4] shadow-md border border-green-500">
                                <img :src="photoData" class="w-full h-full object-cover transform scale-x-[-1]">
                                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                                    Siap Kirim
                                </div>
                            </div>

                            <!-- Canvas Tersembunyi -->
                            <canvas x-ref="canvas" style="display: none;"></canvas>

                            <!-- Tombol Kontrol -->
                            <div class="mt-6 w-full flex justify-center gap-4">
                                <!-- Tombol Jepret -->
                                <button type="button" x-show="!photoData" @click="takeSnapshot()" 
                                        class="flex items-center justify-center w-16 h-16 bg-red-600 rounded-full border-4 border-gray-100 shadow-xl hover:bg-red-700 hover:scale-105 transition transform focus:outline-none ring-2 ring-red-400">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </button>

                                <!-- Tombol Aksi Foto -->
                                <div x-show="photoData" class="flex w-full gap-3 animate-fade-in-up">
                                    <x-secondary-button type="button" @click="retakePhoto()" class="flex-1 justify-center py-3">
                                        {{ __('Foto Ulang') }}
                                    </x-secondary-button>
                                    
                                    <x-primary-button type="submit" class="flex-1 justify-center py-3 bg-green-600 hover:bg-green-700 border-green-700">
                                        {{ __('Kirim Absen') }}
                                    </x-primary-button>
                                </div>
                            </div>
                            
                            <p x-show="!photoData" class="text-xs text-red-500 mt-3 text-center font-medium bg-red-50 px-3 py-1 rounded-full">
                                <span class="font-bold">PERHATIAN:</span> Wajib ambil foto langsung. Tidak bisa unggah dari galeri.
                            </p>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 text-right rounded-b-lg border-t">
                        <button type="button" @click="closeModal()" class="text-sm text-gray-600 hover:text-red-600 font-medium">
                            Batalkan Absensi
                        </button>
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
                 @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                        <p class="font-bold">Gagal</p>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif
                
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                        <p class="font-bold">Berhasil</p>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                         <span class="block sm:inline">{{ $errors->first() }}</span>
                    </div>
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

                                    $userId = auth()->id(); 
                                    $sudahAbsenMandiri = $laporan && $laporan->status == 'Hadir' && $laporan->diabsen_oleh == $userId;
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

                                    $laporanAbsenText = '';
                                    if ($laporan && $laporan->jam_absen) {
                                        $laporanAbsenText = '(' . $laporan->status_keterlambatan . ' pada ' . \Carbon\Carbon::parse($laporan->jam_absen)->format('H:i') . ')';
                                    } elseif ($laporan) {
                                        $laporanAbsenText = '(' . $laporan->status_keterlambatan . ')';
                                    }

                                    $absenDibukaText = ''; 
                                    if (!$laporan && !$bisaAbsen && !$sudahLewat) {
                                        $absenDibukaText = 'Absen dibuka pukul ' . $waktuBukaAbsen->format('H:i');
                                    }
                                @endphp

                                <div class="border rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $bgColorClass }}">
                                    <div class="flex-grow">
                                        <div class="flex items-center gap-4">
                                            <div class="text-center w-24 flex-shrink-0">
                                                <div class="font-bold text-lg text-gray-800">{{ $jamText }}</div>
                                                <div class="text-xs text-gray-500">{{ $waktuMulai->format('H:i') }} - {{ $waktuSelesai->format('H:i') }}</div>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900">{{ $blok['kelas'] }}</div>
                                                <div class="text-sm text-gray-600">{{ $blok['mata_pelajaran'] ?? 'Tanpa Mapel' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                            <div class="text-sm text-gray-500">{{ $absenDibukaText }}</div>
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
                  {{-- [BARU] DAFTAR GURU PIKET HARI INI --}}
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
                                        $noHp = $piket->no_wa ?? $piket->telepon ?? $piket->phone ?? ''; 
                                        $waUrl = '#';
                                        
                                        if($noHp) {
                                            $cleanHp = preg_replace('/[^0-9]/', '', $noHp);
                                            if(substr($cleanHp, 0, 1) == '0'){
                                                $cleanHp = '62' . substr($cleanHp, 1);
                                            }
                                            $waUrl = "https://wa.me/" . $cleanHp;
                                        }
                                    @endphp
                                    
                                    <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-lg hover:shadow-sm transition-shadow">
                                        <div class="flex items-center gap-3">
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