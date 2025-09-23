<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-g">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <meta http-equiv="refresh" content="60">

    <title>Jadwal Real-time</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
    
    <style>
        /* Sembunyikan scrollbar */
        body { overflow: hidden; }
    </style>
</head>

<body class="font-sans antialiased bg-gray-900 text-white">

    <div class="container mx-auto p-8 max-w-full">
        
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-700">
            <div>
                <h1 class="text-5xl font-bold">Jadwal Pelajaran Real-time</h1>
                <p class="text-3xl text-gray-400">{{ $hariIni }}, {{ \Carbon\Carbon::now('Asia/Jakarta')->isoFormat('D MMMM YYYY') }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                @if ($jamKeSekarang)
                    <div class="text-3xl text-yellow-400 font-semibold">
                        Jam ke-{{ $jamKeSekarang->jam_ke }}
                    </div>
                    <div class="text-lg text-gray-400">
                        ({{ Carbon\Carbon::parse($jamKeSekarang->jam_mulai)->format('H:i') }} - {{ Carbon\Carbon::parse($jamKeSekarang->jam_selesai)->format('H:i') }})
                    </div>
                @else
                    <div class="text-3xl text-yellow-400 font-semibold">
                        Di Luar Jam Pelajaran
                    </div>
                @endif
                <div id="live-clock" class="text-6xl font-bold text-white mt-2">...</div> 
            </div>
        </div>

       <div class="overflow-hidden rounded-lg">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-2xl font-medium uppercase tracking-wider w-1/3">Kelas</th>
                            <th class="px-6 py-4 text-left text-2xl font-medium uppercase tracking-wider w-1/3">Guru Pengajar</th>
                            
                            <th class="px-6 py-4 text-left text-2xl font-medium uppercase tracking-wider w-1/3">Status Kehadiran</th>
                        
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        
                        @forelse ($jadwalSekarang as $jadwal)
                            @php
                                // 1. Cari laporan untuk guru ini di data yg sudah kita ambil
                                $laporan = $laporanHariIni->get($jadwal->data_guru_id);
                                $status = $laporan ? $laporan->status : 'Belum Diabsen';
                                
                                // 2. Tentukan warna status
                                $statusColor = 'text-gray-300'; // Default (Belum Diabsen)
                                if ($status == 'Hadir') $statusColor = 'text-green-400';
                                if ($status == 'Sakit') $statusColor = 'text-yellow-400';
                                if ($status == 'Izin') $statusColor = 'text-blue-400';
                                if ($status == 'Alpa') $statusColor = 'text-red-500';
                                if ($status == 'DL') $statusColor = 'text-purple-400';
                            @endphp
                            
                            <tr class="border-b border-gray-700 hover:bg-gray-800">
                                <td class="px-6 py-6 whitespace-nowrap text-4xl font-bold text-yellow-300">{{ $jadwal->kelas }}</td>
                                <td class="px-6 py-6 whitespace-nowrap text-3xl font-medium">{{ $jadwal->dataGuru->nama_guru ?? 'N/A' }}</td>
                                
                                <td class="px-6 py-6 whitespace-nowrap text-3xl font-bold {{ $statusColor }}">
                                    {{ $status }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-3xl text-gray-400 text-center italic">
                                    Tidak ada jadwal mengajar pada jam ini.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

    </div>

    <script>
        function updateClock() {
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                // Ambil waktu dari timezone 'Asia/Jakarta'
                const now = new Date(new Date().toLocaleString("en-US", {timeZone: "Asia/Jakarta"}));
                
                // Format waktu ke HH:MM:SS
                const timeString = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit', 
                    hour12: false 
                });
                clockElement.textContent = timeString;
            }
        }
        // Update jam setiap detik
        setInterval(updateClock, 1000);
        // Panggil sekali saat load agar tidak '...'
        updateClock();
    </script>
</body>
</html>