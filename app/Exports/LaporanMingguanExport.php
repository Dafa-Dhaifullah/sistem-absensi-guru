<?php

namespace App\Exports;

use App\Models\DataGuru;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanMingguanExport implements FromView, ShouldAutoSize
{
    protected $tanggalMulai;
    protected $tanggalSelesai;

    // Terima filter dari controller
    public function __construct(string $tanggalMulai, string $tanggalSelesai)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    /**
     * Method ini akan mengambil data dan mengirimkannya ke file view Blade.
     */
    public function view(): View
    {
        // Query ini SAMA PERSIS dengan query di LaporanController@mingguan
        $semuaGuru = DataGuru::with(['laporanHarian' => function($query) {
            $query->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai]);
        }])->orderBy('nama_guru', 'asc')->get();

        $tanggalRange = \Carbon\Carbon::parse($this->tanggalMulai)->toPeriod(\Carbon\Carbon::parse($this->tanggalSelesai));

        // Kita akan membuat file view ini di langkah selanjutnya
        return view('exports.laporan_mingguan', [
            'semuaGuru' => $semuaGuru,
            'tanggalRange' => $tanggalRange
        ]);
    }
}