<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- KOTAK ABSENSI UTAMA -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="text-lg font-medium text-gray-900">
                        Absensi Hari Ini
                    </h2>

                    <!-- ============================================== -->
                    <!-- ## TAMBAHAN: Tampilkan Pesan Error Validasi ## -->
                    <!-- ============================================== -->
                    @if ($errors->any())
                        <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            <p class="font-bold">Gagal Melakukan Absensi:</p>
                            <ul class="mt-2 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <!-- ============================================== -->


                    @if ($laporanHariIni)
                        <!-- TAMPILAN JIKA SUDAH ABSEN -->
                        <div class="mt-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-800">
                            <h4 class="font-bold">Anda Sudah Melakukan Absensi Hari Ini</h4>
                            <p class="text-sm">Status: <strong>{{ $laporanHariIni->status }}</strong></p>
                            <p class="text-sm">Jam Absen: <strong>{{ \Carbon\Carbon::parse($laporanHariIni->jam_absen)->format('H:i:s') }}</strong></p>
                            <p class="text-sm">Keterangan: <strong>{{ $laporanHariIni->status_keterlambatan }}</strong></p>
                        </div>
                    @elseif (!$jadwalHariIni->isEmpty())
                        <!-- TAMPILAN JIKA BELUM ABSEN & ADA JADWAL -->
                        <div class="mt-6">
                            <p class="text-sm text-gray-600 mb-4">
                                Untuk absen, silakan scan QR Code yang ditampilkan di monitor, lalu ambil foto selfie.
                            </p>
                            
                            <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="qr_token" id="qr_token">

                                <!-- Bagian 1: Scan QR Code -->
                                <div id="qr-scanner-section">
                                    <div id="qr-reader" class="w-full md:w-80 h-80 border-2 border-dashed rounded-lg"></div>
                                    <p id="qr-reader-status" class="text-sm text-gray-500 mt-2 text-center"></p>
                                </div>

                                <!-- Bagian 2: Ambil Selfie (Awalnya tersembunyi) -->
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
                        <!-- TAMPILAN JIKA TIDAK ADA JADWAL -->
                         <div class="mt-6 p-4 bg-gray-100 border-l-4 border-gray-500 text-gray-800">
                            <p class="text-sm font-medium">Anda tidak memiliki jadwal mengajar hari ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- JADWAL HARI INI -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Jadwal Mengajar Anda Hari Ini</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead><tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jam Ke-</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Pelajaran</th>
                        </tr></thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($jadwalHariIni as $jadwal)
                                <tr>
                                    <td class="px-6 py-4">{{ $jadwal->jam_ke }}</td>
                                    <td class="px-6 py-4">{{ $jadwal->kelas }}</td>
                                    <td class="px-6 py-4">{{ $jadwal->mata_pelajaran ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada jadwal.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- INFO PIKET HARI INI -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h2 class="text-lg font-medium text-gray-900">Informasi Piket Hari Ini (Sesi {{ $sesiSekarang }})</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($guruPiketHariIni as $piket)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium">{{ $piket->name }}</span>
                            @if($piket->no_wa)
                                @php
                                    $waNumber = preg_replace('/^0/', '62', $piket->no_wa);
                                    $waLink = "https://wa.me/{$waNumber}";
                                @endphp
                                <a href="{{ $waLink }}" target="_blank" class="text-sm text-green-600 font-semibold hover:underline">Hubungi via WhatsApp</a>
                            @else
                                <span class="text-sm text-gray-400">No. WA tidak tersedia</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada guru piket yang diatur untuk sesi ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('form-absen')) {
                const qrScannerSection = document.getElementById('qr-scanner-section');
                const selfieSection = document.getElementById('selfie-section');
                const qrTokenInput = document.getElementById('qr_token');
                const statusElement = document.getElementById('qr-reader-status');

                function onScanSuccess(decodedText, decodedResult) {
                    html5QrcodeScanner.clear();
                    qrTokenInput.value = decodedText;
                    qrScannerSection.classList.add('hidden');
                    selfieSection.classList.remove('hidden');
                }

                function onScanFailure(error) {
                    statusElement.textContent = 'Arahkan kamera ke QR Code...';
                }

                let html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        });
    </script>
</x-app-layout>

