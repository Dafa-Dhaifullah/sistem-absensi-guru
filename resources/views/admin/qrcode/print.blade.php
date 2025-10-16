<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code - {{ $kelas }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-10 rounded-lg shadow-xl text-center">
        <h1 class="text-3xl font-bold text-gray-800">ABSENSI KELAS</h1>
        <h2 class="text-5xl font-extrabold text-blue-600 my-4">{{ $kelas }}</h2>

        <div class="flex justify-center my-6">
            <!-- Library Simple QR Code akan otomatis membuat gambar dari teks ini -->
            {!! QrCode::size(256)->generate($kelas) !!}
        </div>

        <p class="text-gray-600">Scan QR Code ini untuk melakukan absensi.</p>

        <div class="mt-8 no-print">
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700">
                Cetak Halaman Ini
            </button>
        </div>
    </div>
</body>
</html>