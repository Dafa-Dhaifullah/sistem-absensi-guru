<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">
                        Absensi Hari Ini
                    </h2>

                    @if ($laporanHariIni)
                        <div class="mt-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-800">
                            <h4 class="font-bold">Anda Sudah Melakukan Absensi Hari Ini</h4>
                            <p class="text-sm">Status: <strong>{{ $laporanHariIni->status }}</strong></p>
                            <p class="text-sm">Jam Absen: <strong>{{ \Carbon\Carbon::parse($laporanHariIni->jam_absen)->format('H:i:s') }}</strong></p>
                            <p class="text-sm">Keterangan: <strong>{{ $laporanHariIni->status_keterlambatan }}</strong></p>
                        </div>
                    @elseif (!$jadwalHariIni->isEmpty())
                        <div class="mt-6">
                            <p class="text-sm text-gray-600 mb-4">
                                Untuk absen, silakan scan QR Code yang ditampilkan di monitor, lalu ambil foto selfie.
                            </p>
                            
                            <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="qr_token" id="qr_token">

                                <div id="qr-scanner-section">
                                    <div id="qr-reader" class="w-full md:w-80 h-80 border-2 border-dashed rounded-lg"></div>
                                    <p id="qr-reader-status" class="text-sm text-gray-500 mt-2 text-center"></p>
                                </div>

                                <div id="selfie-section" class="hidden mt-6">
                                    <div class="p-4 bg-blue-100 border border-blue-300 rounded-lg">
                                        <p class="font-semibold text-blue-800">✔️ QR Code berhasil dipindai!</p>
                                        <p class="text-sm text-blue-700">Langkah terakhir, silakan ambil foto selfie Anda.</p>
                                    </div>

                                    <div class="mt-4">
                                        <x-input-label for="foto_selfie" :value="__('Ambil Foto Selfie')" />
                                        <input id="foto_selfie" name="foto_selfie" type="file" accept="image/*" capture="user" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                                        <x-input-error :messages="$errors->get('foto_selfie')" class="mt-2" />
                                    </div>
                                    
                                    <div class="mt-4">
                                        <x-primary-button type="submit">
                                            {{ __('Kirim Absensi') }}
                                        </x-primary-button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="mt-6 p-4 bg-gray-100 border-l-4 border-gray-500 text-gray-800">
                            <p class="text-sm font-medium">Anda tidak memiliki jadwal mengajar hari ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                </div>

        </div>
    </div>
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Hanya jalankan skrip jika form absen ada
            if (document.getElementById('form-absen')) {
                
                const qrScannerSection = document.getElementById('qr-scanner-section');
                const selfieSection = document.getElementById('selfie-section');
                const qrTokenInput = document.getElementById('qr_token');
                const statusElement = document.getElementById('qr-reader-status');

                function onScanSuccess(decodedText, decodedResult) {
                    // Berhenti scan setelah berhasil
                    html5QrcodeScanner.clear();
                    
                    // Isi token ke input tersembunyi
                    qrTokenInput.value = decodedText;

                    // Tampilkan bagian selfie
                    qrScannerSection.classList.add('hidden');
                    selfieSection.classList.remove('hidden');
                }

                function onScanFailure(error) {
                    // Tidak melakukan apa-apa, agar scan terus berjalan
                    statusElement.textContent = 'Arahkan kamera ke QR Code...';
                }

                // Buat instance scanner
                let html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader", 
                    { fps: 10, qrbox: {width: 250, height: 250} }, 
                    /* verbose= */ false
                );

                // Mulai scan
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        });
    </script>
</x-app-layout>