<x-teacher-layout>
    {{-- Auto-refresh setiap 60 detik --}}
    <x-slot name="headerScripts">
        <meta http-equiv="refresh" content="60">
    </x-slot>
    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Pemantauan Piket') }}
        </h2>
    </x-slot>

    {{-- Alpine root mencakup seluruh halaman --}}
    <div class="py-12" x-data="piketPage()">
        {{-- Tambah pb-24 agar tidak ketutupan tombol fixed --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8 pb-24">

            <a href="{{ route('guru.dashboard') }}"
               class="block w-full p-4 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition duration-150 text-center">
                <h3 class="text-xl font-bold">Buka Dasbor Absensi Pribadi</h3>
                <p class="text-indigo-100 text-sm">Klik untuk melakukan absensi mandiri (Scan QR + Selfie).</p>
            </a>

            {{-- Petunjuk (kolapsibel, persist di localStorage) --}}
            <div
                x-data="{ open: JSON.parse(localStorage.getItem('petunjukOpen') ?? 'true') }"
                x-effect="localStorage.setItem('petunjukOpen', JSON.stringify(open))"
                class="bg-white p-6 rounded-xl shadow-sm"
            >
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
                                <p class="text-sm text-gray-600">Lihat tabel di bawah untuk memantau status guru. Halaman ini akan memuat ulang data otomatis setiap 1 menit.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">2</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Hubungi Guru</h4>
                                <p class="text-sm text-gray-600">Jika ada guru berstatus "Belum Absen" mendekati/lebih dari jam masuk, gunakan tombol WhatsApp.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Gunakan Aksi Override</h4>
                                <p class="text-sm text-gray-600">Jika guru melapor (Sakit/Izin/DL) atau terkendala, ubah statusnya dan <strong>wajib isi keterangan</strong>.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">4</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Isi Logbook (Opsional)</h4>
                                <p class="text-sm text-gray-600">Catat kejadian penting dan tindak lanjutnya pada formulir logbook.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">5</div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Simpan Perubahan</h4>
                                <p class="text-sm text-gray-600">Klik tombol "Simpan Perubahan" di bawah untuk merekam semua aksi Anda.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Keterangan Warna --}}
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

            {{-- ================== ANCHOR SEARCH ================== --}}
            <div id="search"></div>

            {{-- Bar Pencarian Guru (di bawah keterangan warna) --}}
            <form method="GET" action="{{ request()->url() }}#search" id="formSearch" class="bg-white p-4 rounded-xl shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Cari Guru</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="{{ old('q', $q ?? '') }}"
                            placeholder="Ketik nama guru..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Cari
                        </button>
                        @if (!empty($q))
                            <a href="{{ request()->url() }}#search" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    @php $filtered = isset($guruWajibHadir) ? $guruWajibHadir->count() : 0; @endphp
                    Menampilkan <span class="font-semibold">{{ $filtered }}</span> dari <span class="font-semibold">{{ $totalGuru ?? $filtered }}</span> guru.
                    @if (!empty($q))
                        <span class="ml-1">Kata kunci: “<span class="font-semibold">{{ $q }}</span>”.</span>
                    @endif
                </div>
            </form>

            {{-- Form Utama --}}
            <form id="formPiket" action="{{ route('piket.laporan-harian.store') }}" method="POST">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Kehadiran Guru Hari Ini</h3>
                        <p class="text-sm text-gray-600">
                            Hari: <span class="font-semibold">{{ $hariIni ?? 'N/A' }}</span> |
                            Tipe Minggu: <span class="font-semibold">{{ $tipeMinggu ?? 'N/A' }}</span>
                        </p>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @forelse ($guruWajibHadir as $guru)
                            @php
                                // Kelompokkan jadwal per guru menjadi blok
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
                                {{-- Info Guru + WhatsApp --}}
                                <div class="flex justify-between items-center">
                                    <h4 class="text-base font-semibold text-gray-800">{{ $guru->name }}</h4>
                                    @if ($guru->no_wa)
                                        @php
                                            // Normalisasi: 0..., +62..., 62... -> 62...
                                            $waNumber = preg_replace('/^(0|\+?62)/', '62', $guru->no_wa);
                                            $waLink = "https://wa.me/{$waNumber}?text=Assalamualaikum%20Bapak/Ibu%20{$guru->name},%20mohon%20konfirmasi%20kehadirannya%20untuk%20jadwal%20hari%20ini.%20Terima%20kasih.";
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" class="inline-flex items-center text-xs text-green-600 font-semibold hover:text-green-700">
                                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.06 21.94L7.31 20.58C8.75 21.38 10.36 21.82 12.04 21.82C17.5 21.82 21.95 17.37 21.95 11.91C21.95 6.45 17.5 2 12.04 2M12.04 20.13C10.5 20.13 9 19.7 7.71 19.01L7.42 18.84L4.32 19.65L5.16 16.63L4.97 16.32C4.22 14.91 3.81 13.36 3.81 11.91C3.81 7.39 7.51 3.69 12.04 3.69C14.25 3.69 16.31 4.54 17.87 6.1C19.43 7.66 20.28 9.72 20.28 11.91C20.28 16.43 16.57 20.13 12.04 20.13M17.17 14.44C16.92 14.32 15.66 13.71 15.44 13.62C15.21 13.53 15.04 13.47 14.88 13.72C14.71 13.97 14.21 14.58 14.03 14.75C13.86 14.92 13.69 14.95 13.44 14.82C13.19 14.7 12.22 14.39 11.09 13.39C10.21 12.63 9.6 11.75 9.43 11.5C9.26 11.25 9.39 11.13 9.51 11.01C9.62 10.9 9.76 10.73 9.89 10.59C10.01 10.45 10.07 10.33 10.19 10.1C10.3 9.87 10.24 9.68 10.18 9.56C10.12 9.44 9.62 8.2 9.4 7.68C9.18 7.16 8.97 7.23 8.79 7.22C8.61 7.21 8.44 7.21 8.28 7.21C8.11 7.21 7.8 7.27 7.55 7.52C7.3 7.77 6.8 8.24 6.8 9.31C6.8 10.38 7.58 11.41 7.7 11.56C7.82 11.71 9.27 14.01 11.5 14.93C13.73 15.84 14.51 15.5 14.99 15.47C15.47 15.44 16.72 14.83 16.95 14.22C17.17 13.61 17.17 13.11 17.11 13C17.05 12.89 16.88 12.83 16.63 12.71Z"/>
                                            </svg>
                                            Hubungi
                                        </a>
                                    @endif
                                </div>

                                {{-- Detail blok jadwal + override --}}
                                <div class="mt-4 space-y-3">
                                    @foreach ($jadwalBlok as $blok)
                                        @php
                                            $laporan = $laporanHariIni->get($blok['jadwal_ids'][0]); // status jam pertama blok
                                            $isHadir = $laporan && $laporan->status == 'Hadir';

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
                                                    {{-- Kirim semua ID jadwal dalam blok --}}
                                                    @foreach($blok['jadwal_ids'] as $jadwalId)
                                                        <input type="hidden" name="jadwal_ids_override[{{ $jadwalId }}]" value="1">
                                                    @endforeach
                                                    <select name="status_override[{{ $blok['jadwal_ids'][0] }}]" class="w-full rounded-md text-sm border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ $isHadir ? 'disabled' : '' }}>
                                                        <option value="">-- Override --</option>
                                                        <option value="Sakit">Sakit</option>
                                                        <option value="Izin">Izin</option>
                                                        <option value="Alpa">Alpa</option>
                                                        <option value="DL">Dinas Luar</option>
                                                    </select>
                                                    <input type="text" name="keterangan_piket[{{ $blok['jadwal_ids'][0] }}]" placeholder="Catatan..." class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" {{ $isHadir ? 'disabled' : '' }}>
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

                {{-- Logbook --}}
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
            </form>

        </div>
    </div>

    {{-- Tombol 'Simpan' fixed --}}
    <div class="fixed bottom-0 left-0 w-full bg-white bg-opacity-90 backdrop-blur-sm border-t border-gray-200 z-40">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="flex items-center justify-end h-20 px-4 sm:px-0">
      {{-- Perhatikan: type="submit" + form="formPiket" --}}
      <x-primary-button type="submit" form="formPiket" id="btnSimpan">
        {{ __('Simpan Perubahan') }}
      </x-primary-button>
    </div>
  </div>

    @push('scripts')
<script>
(function () {
  const form = document.getElementById('formPiket');
  if (!form) return;

  let confirmedOnce = false;

  form.addEventListener('submit', function (e) {
    if (confirmedOnce) return;
    
    e.preventDefault();
    e.stopPropagation();

    const go = () => {
      confirmedOnce = true;
      try {
        sessionStorage.setItem('piket:scroll', JSON.stringify({ 
          x: window.scrollX, 
          y: window.scrollY, 
          t: Date.now() 
        }));
      } catch (_) {}
      form.submit();
    };

    if (typeof Swal !== 'undefined' && Swal.fire) {
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
        if (result.isConfirmed) go();
      });
    } else {
      console.warn('Sweet Alert tidak tersedia');
      if (confirm('Simpan perubahan?')) go();
    }
  }, { capture: true });
})();

// Scroll restoration
if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
(function () {
  const KEY = 'piket:scroll';
  
  function saveScroll() {
    try { 
      sessionStorage.setItem(KEY, JSON.stringify({ 
        x: window.scrollX, 
        y: window.scrollY, 
        t: Date.now() 
      })); 
    } catch (_) {}
  }
  
  function restoreScroll() {
    const raw = sessionStorage.getItem(KEY);
    if (!raw) return;
    try {
      const {x, y, t} = JSON.parse(raw);
      if (Date.now() - t > 5000) return;
      let tries = 0;
      const tick = () => {
        window.scrollTo(x, y);
        if (++tries < 12) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    } catch (_) {}
  }
  
  window.addEventListener('beforeunload', saveScroll, {capture: true});
  document.addEventListener('visibilitychange', () => { 
    if (document.visibilityState === 'hidden') saveScroll(); 
  });
  window.addEventListener('pageshow', restoreScroll);
})();

// Focus search input
document.addEventListener('DOMContentLoaded', () => {
  @if (!empty($q))
    const el = document.getElementById('q');
    if (el) { 
      el.focus(); 
      el.selectionStart = el.selectionEnd = el.value.length; 
    }
    document.getElementById('search')?.scrollIntoView({ behavior: 'auto', block: 'start' });
  @endif
});
</script>
@endpush
</x-teacher-layout>
