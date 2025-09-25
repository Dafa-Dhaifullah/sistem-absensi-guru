<?php

namespace App\Imports;

use App\Models\DataGuru;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Untuk membaca baris header
use Maatwebsite\Excel\Concerns\WithValidation; // Untuk validasi

class GuruImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Fungsi ini akan dipanggil untuk setiap baris di Excel
        return new DataGuru([
            // 'nama_guru' & 'nip' HARUS SAMA dengan nama header di file Excel Anda
            'nama_guru' => $row['nama_guru'],
            'nip'       => $row['nip'],
        ]);
    }

    /**
     * Tentukan aturan validasi untuk setiap baris.
     */
    public function rules(): array
    {
        return [
            'nama_guru' => 'required|string|max:255',
            // 'unique:data_guru' akan memastikan NIP tidak duplikat
            'nip' => 'nullable|string|max:50|unique:data_guru,nip', 
        ];
    }
}