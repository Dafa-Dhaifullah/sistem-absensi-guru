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
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Tentukan aturan validasi untuk setiap baris di Excel.
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|regex:/^[a-zA-Z0-9\s]+$/',
            'email' => 'nullable|email|max:255|unique:users,email',
            'nip' => ['nullable', 'regex:/^[0-9]+$/', 'unique:users,nip'],
            'no_wa' => ['nullable', 'regex:/^[0-9]+$/'], 
            'role' => 'required|in:admin,kepala_sekolah,guru',
        ];
    }

    /**
     * Pesan error custom untuk validasi.
     */
    public function customValidationMessages()
    {
        return [
            'username.regex' => 'Username di Excel hanya boleh berisi huruf, angka, dan spasi.',
             'nama.required' => 'Kolom nama wajib diisi.',
            'username.required' => 'Kolom username wajib diisi.',
            'role.required' => 'Kolom role wajib diisi.',

            'username.unique' => 'Username ":value" sudah terdaftar di sistem.',
            'email.unique' => 'Email ":value" sudah terdaftar di sistem.',
            'nip.unique' => 'NIP ":value" sudah terdaftar di sistem.',

            'role.in' => 'Nilai untuk kolom role tidak valid. Gunakan: admin, kepala_sekolah, atau guru.',
            '*.numeric' => 'Kolom :attribute harus berupa angka.',

            'nip.regex' => 'Kolom nip hanya boleh berisi angka.',
            'no_wa.regex' => 'Kolom no_wa hanya boleh berisi angka.',
        ];
    }

    public function batchSize(): int
    {
        return 500;
    }
}
