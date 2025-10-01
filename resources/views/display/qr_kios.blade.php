<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Absensi</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-xl text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Scan untuk Absensi</h1>
        <p class="text-gray-600 mb-4">QR Code ini akan diperbarui secara otomatis.</p>

        <div id="qrcode" class="flex justify-center"></div>

        <p id="timer" class="mt-4 text-lg font-medium text-red-500"></p>
    </div>

    <script>
        const qrCodeElement = document.getElementById('qrcode');
        const timerElement = document.getElementById('timer');
        let qrCodeInstance = null;
        let countdownInterval = null;

        function generateQRCode() {
            // Hapus QR code lama
            qrCodeElement.innerHTML = '';

            // Minta token baru dari server
            fetch('{{ route("qrcode.generate") }}')
                .then(response => response.json())
                .then(data => {
                    // Buat instance QR Code baru dengan token yang diterima
                    qrCodeInstance = new QRCode(qrCodeElement, {
                        text: data.token,
                        width: 256,
                        height: 256,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });

                    // Mulai hitung mundur 60 detik
                    startCountdown(60);
                })
                .catch(error => {
                    console.error('Gagal mengambil token QR:', error);
                    qrCodeElement.innerHTML = '<p class="text-red-500">Gagal memuat QR Code. Cek koneksi.</p>';
                });
        }

        function startCountdown(seconds) {
            // Hentikan interval lama jika ada
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            let counter = seconds;
            timerElement.textContent = `Berubah dalam ${counter} detik...`;

            countdownInterval = setInterval(() => {
                counter--;
                timerElement.textContent = `Berubah dalam ${counter} detik...`;
                if (counter <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
        }

        // Panggil fungsi pertama kali saat halaman dimuat
        generateQRCode();

        // Atur agar fungsi dipanggil setiap 60 detik (60000 milidetik)
        setInterval(generateQRCode, 60000);
    </script>
</body>
</html>