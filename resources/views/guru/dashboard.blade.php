<x-teacher-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Guru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white shadow-sm sm:rounded-xl">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-900">
                        Absensi Hari Ini
                    </h2>

                    @if ($errors->any())
                        <div class="mt-6 p-4 rounded-lg bg-red-50 text-red-800 flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-sm">Gagal Melakukan Absensi:</p>
                                <ul class="mt-1 list-disc list-inside text-sm">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if ($laporanHariIni)
                        <div class="mt-6 flex items-center gap-6 p-6 bg-green-50 rounded-lg">
                            <div class="flex-shrink-0 w-16 h-16 bg-green-200 text-green-700 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-green-800 text-lg">Anda Sudah Melakukan Absensi Hari Ini</h3>
                                <div class="mt-2 text-sm text-green-700 grid grid-cols-2 gap-x-4 gap-y-1">
                                    <span>Status:</span> <strong class="font-semibold">{{ $laporanHariIni->status }}</strong>
                                    <span>Jam Absen:</span> <strong class="font-semibold">{{ \Carbon\Carbon::parse($laporanHariIni->jam_absen)->format('H:i:s') }}</strong>
                                    <span>Keterangan:</span> <strong class="font-semibold">{{ $laporanHariIni->status_keterlambatan }}</strong>
                                </div>
                            </div>
                        </div>
                    @elseif (!$jadwalHariIni->isEmpty())
                        <div class="mt-6">
                            <ol class="flex items-center w-full text-sm font-medium text-center text-gray-500">
                                <li id="step-1-indicator" class="flex md:w-full items-center text-indigo-600 sm:after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10">
                                    <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200">
                                        <svg class="w-4 h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>
                                        1. <span class="hidden sm:inline-flex sm:ms-2">Scan QR</span>
                                    </span>
                                </li>
                                <li id="step-2-indicator" class="flex items-center">
                                    <span class="flex items-center">
                                        <span class="me-2">2</span>
                                        Ambil Selfie
                                    </span>
                                </li>
                            </ol>

                            <form id="form-absen" action="{{ route('guru.absen.store') }}" method="POST" enctype="multipart/form-data" class="mt-6">
                                @csrf
                                <input type="hidden" name="qr_token" id="qr_token">

                                <div id="qr-scanner-section" class="flex flex-col items-center">
                                    <p class="text-sm text-gray-600 mb-4 text-center max-w-md">
                                        Untuk memulai, silakan pindai (scan) QR Code yang ditampilkan pada monitor di ruang guru.
                                    </p>
                                    <div id="qr-reader" class="w-full max-w-sm h-auto border-2 border-dashed rounded-lg"></div>
                                    <p id="qr-reader-status" class="text-sm text-gray-500 mt-2 text-center h-5"></p>
                                </div>

                                <div id="selfie-section" class="hidden">
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
                                        <x-primary-button type="submit">
                                            {{ __('Kirim Absensi') }}
                                        </x-primary-button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="mt-6 flex items-center gap-6 p-6 bg-slate-50 rounded-lg">
                            <div class="flex-shrink-0 w-16 h-16 bg-slate-200 text-slate-600 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0h18M12 12.75h.008v.008H12v-.008z" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg">Tidak Ada Jadwal Mengajar</h3>
                                <p class="text-sm text-slate-600 mt-1">Anda tidak memiliki jadwal mengajar hari ini, silakan beristirahat.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white shadow-sm sm:rounded-xl">
                     <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">Jadwal Mengajar Anda Hari Ini</h2>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="border-b">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Ke-</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($jadwalHariIni as $jadwal)
                                        <tr class="odd:bg-white even:bg-slate-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $jadwal->jam_ke }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $jadwal->kelas }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ $jadwal->mata_pelajaran ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-6 py-6 text-center text-sm text-gray-500">Tidak ada jadwal.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                     </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-xl">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900">Informasi Piket Hari Ini</h2>
                        <p class="mt-1 text-sm text-gray-600">Sesi Saat Ini: <span class="font-semibold text-indigo-600">{{ $sesiSekarang }}</span></p>
                        <div class="mt-4 space-y-3">
                            @forelse ($guruPiketHariIni as $piket)
                                <div class="flex justify-between items-center p-4 bg-slate-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-slate-200 text-slate-600 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <span class="font-medium text-gray-800">{{ $piket->name }}</span>
                                    </div>
                                    @if($piket->no_wa)
                                        @php
                                            $waNumber = preg_replace('/^0/', '62', $piket->no_wa);
                                            $waLink = "https://wa.me/{$waNumber}";
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" class="inline-flex items-center text-sm text-green-600 font-semibold hover:text-green-700 transition duration-150">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.149-.172.198-.296.297-.495.099-.198.05-.371-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01s-.521.074-.792.372c-.272.296-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.289.173-1.413z"/></svg>
                                            Hubungi
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400">No. WA tidak tersedia</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">Belum ada guru piket yang diatur untuk sesi ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    @push('scripts')
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('form-absen')) {
                const qrScannerSection = document.getElementById('qr-scanner-section');
                const selfieSection = document.getElementById('selfie-section');
                const qrTokenInput = document.getElementById('qr_token');
                const statusElement = document.getElementById('qr-reader-status');

                // Indikator Stepper
                const step1 = document.getElementById('step-1-indicator');
                const step2 = document.getElementById('step-2-indicator');

                function onScanSuccess(decodedText, decodedResult) {
                    html5QrcodeScanner.clear();
                    qrTokenInput.value = decodedText;
                    
                    qrScannerSection.classList.add('hidden');
                    selfieSection.classList.remove('hidden');

                    // Update Stepper
                    step1.classList.remove('text-indigo-600');
                    step2.classList.add('text-indigo-600');
                    step2.innerHTML = `<span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200"><svg class="w-4 h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg> 2. <span class="hidden sm:inline-flex sm:ms-2">Ambil Selfie</span></span>`;
                }

                function onScanFailure(error) {
                    statusElement.textContent = 'Arahkan kamera ke QR Code...';
                }

                let html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: {width: 250, height: 250}, useBarCodeDetectorIfSupported: true }, false);
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }
        });
    </script>
    @endpush
</x-teacher-layout>