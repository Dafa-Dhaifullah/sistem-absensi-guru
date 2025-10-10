<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LaporanHarian;
use Illuminate\Support\Facades\Storage;

class HapusFotoSelfieLama extends Command
{
    /**
     * Nama dan signature dari console command.
     */
    protected $signature = 'app:hapus-foto-selfie-lama';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Menghapus file foto selfie yang usianya lebih dari satu bulan untuk menghemat storage.';

    /**
     * Jalankan console command.
     */
    public function handle()
    {
        $this->info('Mencari foto selfie lama (lebih dari 1 bulan)...');

        // REVISI: Cari laporan yang lebih lama dari 1 bulan
        $laporanLama = LaporanHarian::where('tanggal', '<=', now()->subMonth())
                                    ->whereNotNull('foto_selfie_path')
                                    ->get();

        if ($laporanLama->isEmpty()) {
            $this->info('Tidak ada foto lama yang ditemukan untuk dihapus.');
            return 0;
        }

        $jumlahDihapus = 0;
        foreach ($laporanLama as $laporan) {
            // Cek apakah file benar-benar ada di storage
            if (Storage::exists($laporan->foto_selfie_path)) {
                // Hapus file dari storage
                Storage::delete($laporan->foto_selfie_path);

                // Kosongkan path di database agar tidak menjadi link rusak
                $laporan->foto_selfie_path = null;
                $laporan->save();

                $jumlahDihapus++;
            }
        }

        $this->info("Selesai. Sebanyak {$jumlahDihapus} file foto selfie lama telah dihapus.");
        return 0;
    }
} 