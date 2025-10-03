<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class PenggunaImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts
{
    /**
    * @param array $row
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new User([
            'name'     => $row['nama'],
            'username' => $row['username'],
            'email'    => $row['email'],
            'nip'      => $row['nip'],
            'no_wa'    => $row['no_wa'],
            'role'     => $row['role'],
            // Otomatis buat password default, misal: 'password123'
            'password' => Hash::make('password123'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'no_wa' => 'nullable|string|max:20',
            'role' => 'required|in:admin,kepala_sekolah,piket,guru',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }
}