<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="60">
    <title>Jadwal Real-time</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <style>
        /* Mencegah scroll di layar TV */
        body { overflow: hidden; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-900 text-white">
    {{-- 
    ==========================================================
    == PERUBAHAN: Font di header sedikit dikecilkan agar pas ==
    ==========================================================
    --}}
    <div class="container mx-auto p-4 md:p-8 max-w-full">
        <div class="flex justify-between items-center mb-4 md:mb-6 pb-4 border-b border-gray-700">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">Jadwal Pelajaran Real-time</h1>
                <p class="text-xl md:text-2xl text-gray-400">{{ $hariIni }}, {{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('D MMMM YYYY') }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                @if ($jamKeSekarang)
                    <div class="text-2xl md:text-3xl text-yellow-400 font-semibold">
                        Jam ke-{{ $jamKeSekarang->jam_ke }}
                    </div>
                    <div class="text-base md:text-lg text-gray-400">
                        ({{ Carbon\Carbon::parse($jamKeSekarang->jam_mulai)->format('H:i') }} - {{ Carbon\Carbon::parse($jamKeSekarang->jam_selesai)->format('H:i') }})
                    </div>
                @else
                    <div class="text-2xl md:text-3xl text-yellow-400 font-semibold">
                        Di Luar Jam Pelajaran
                    </div>
                @endif
                <div id="live-clock" class="text-4xl md:text-5xl font-bold text-white mt-2">...</div> 
            </div>
        </div>

        {{-- 
        ==========================================================
        == PERUBAHAN: Layout Grid 3 Kolom ==
        ==========================================================
        --}}
        <div class="overflow-hidden">
            @php
                // Hitung total jadwal dan bagi menjadi 3 kolom
                $totalJadwal = $jadwalSekarang->count();
                // (35 / 3 = 11.6 -> ceil = 12 item per kolom)
                $chunks = $totalJadwal > 0 ? $jadwalSekarang->chunk(ceil($totalJadwal / 3)) : collect();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4">

                @forelse ($chunks as $chunk)
                    {{-- Setiap $chunk adalah satu kolom --}}
                    <div class="flex flex-col">
                        <table class="min-w-full">
                            <thead class="bg-gray-800">
                                <tr>
                                    {{-- PERUBAHAN: Header tabel dikecilkan --}}
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Kelas</th>
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Guru</th>
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-900 divide-y divide-gray-700">
                                @foreach ($chunk as $jadwal)
                                    @php
                                        // =================================
                                        // == PERBAIKAN BUG LOGIKA ==
                                        // =================================
                                        // 1. Dapatkan laporan berdasarkan ID Jadwal (sesuai controller)
                                        $laporan = $laporanHariIni->get($jadwal->id); 
                                        
                                        $status = 'Belum Diabsen';
                                        $statusColor = 'text-gray-300';

                                        if ($laporan) {
                                            $status = $laporan->status; // Cth: 'Hadir', 'Sakit', 'Izin'
                                            
                                            // 2. PERBAIKAN: Status "Terlambat" akan tetap tampil sebagai "Hadir" (sesuai permintaan)
                                            if ($status == 'Hadir') {
                                                $statusColor = 'text-green-400';
                                            } elseif ($status == 'Sakit') {
                                                $statusColor = 'text-yellow-400';
                                            } elseif ($status == 'Izin') {
                                                $statusColor = 'text-blue-400';
                                            } elseif ($status == 'Alpa') {
                                                $statusColor = 'text-red-500';
                                            } elseif ($status == 'DL') {
                                                $statusColor = 'text-purple-400';
                                            }
                                        }
                                    @endphp
                                    
                                    <tr class="hover:bg-gray-800">
                                        <td class="px-4 py-2 whitespace-nowrap text-xl font-bold text-yellow-300">{{ $jadwal->kelas }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-lg font-medium">{{ $jadwal->user->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-lg font-bold {{ $statusColor }}">
                                            {{ $status }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    {{-- Tampilan jika tidak ada jadwal sama sekali --}}
                    <div class="md:col-span-3">
                         <table class="min-w-full">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Kelas</th>
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Guru</th>
                                    <th class="px-4 py-3 text-left text-base md:text-lg font-medium uppercase tracking-wider w-1/3">Status</th>
                                </tr>
                            </thead>
                        </table>
                        <div class="bg-gray-900 px-6 py-10 text-2xl text-gray-400 text-center italic">
                            Tidak ada jadwal mengajar pada jam ini.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- SCRIPT (Tidak Berubah) --}}
    <script>
        function updateClock() {
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                const now = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Jakarta"}));
                const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                clockElement.textContent = timeString;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>