<?php

namespace App\Imports;

use App\Models\DataGuru;
use App\Models\JadwalPelajaran;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class JadwalPelajaranImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $guruByNip;
    private $guruByName;

    // Constructor ini akan membuat "kamus" guru sebelum import dimulai
    public function __construct()
    {
        // Peta 1: '12345' => 1 (ID Guru)
        $this->guruByNip = DataGuru::whereNotNull('nip')->pluck('id', 'nip');
        // Peta 2: 'Budi Santoso' => 2 (ID Guru)
        $this->guruByName = DataGuru::pluck('id', 'nama_guru');
    }

    public function model(array $row)
    {
        $guruId = null;

        // Prioritas 1: Cari berdasarkan NIP jika ada isinya
        if (!empty($row['nip_guru'])) {
            $guruId = $this->guruByNip->get($row['nip_guru']);
        } 
        // Prioritas 2: Jika NIP kosong, cari berdasarkan Nama Guru
        else if (!empty($row['nama_guru'])) {
            $guruId = $this->guruByName->get($row['nama_guru']);
        }

        // Jika guru tidak ditemukan dengan NIP maupun Nama, lewati baris ini
        if (!$guruId) {
            return null;
        }

        // 'jam_ke' di Excel bisa berupa "1,2,3" atau hanya "1"
        $jamKeArray = explode(',', $row['jam_ke']);

        // Looping untuk membuat multiple record untuk setiap jam
        foreach ($jamKeArray as $jam) {
            // Pastikan tidak ada data duplikat sebelum create
            JadwalPelajaran::firstOrCreate(
                // Kunci pencarian
                [
                    'data_guru_id'    => $guruId,
                    'hari'            => $row['hari'],
                    'jam_ke'          => (int) trim($jam),
                    'tipe_blok'       => $row['tipe_blok'],
                    'kelas'           => $row['kelas'],
                ],
                // Data tambahan (hanya jika record baru dibuat)
                [
                    'mata_pelajaran'  => $row['mata_pelajaran'],
                ]
            );
        }

        // Karena kita sudah handle manual, return null
        return null;
    }

    public function rules(): array
    {
        return [
            // NIP sekarang opsional, tapi nama_guru wajib jika NIP kosong
            'nip_guru' => 'nullable',
            'nama_guru' => 'required_without:nip_guru|string',
            'mata_pelajaran' => 'nullable|string',
            'kelas' => 'required|string',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke' => 'required|string',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_guru.required_without' => 'Kolom nama_guru wajib diisi jika nip_guru kosong.',
        ];
    }

    public function batchSize(): int { return 1000; }
    public function chunkSize(): int { return 1000; }
}