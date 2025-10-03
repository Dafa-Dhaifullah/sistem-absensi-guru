<?php

namespace App\Exports;

use App\Models\User; // <-- REVISI: Menggunakan model User
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanMingguanExport implements FromView, ShouldAutoSize
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    
    public function __construct(string $tanggalMulai, string $tanggalSelesai)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    public function view(): View
    {
        // REVISI: Mengambil data dari model User dengan role 'guru'
        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function($query) {
                $query->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai]);
            }])->orderBy('name', 'asc')->get();

        $tanggalRange = \Carbon\Carbon::parse($this->tanggalMulai)->locale('id_ID')->toPeriod(\Carbon\Carbon::parse($this->tanggalSelesai));

        return view('exports.laporan_mingguan', [
            'semuaGuru' => $semuaGuru,
            'tanggalRange' => $tanggalRange
        ]);
    }
}