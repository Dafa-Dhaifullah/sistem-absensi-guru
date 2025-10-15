<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Baris Bahasa untuk Validasi
    |--------------------------------------------------------------------------
    */

    'required' => 'Kolom :attribute wajib diisi.',
    'unique' => ':attribute ini sudah terdaftar di sistem.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'max' => [
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'in' => 'Nilai yang dipilih untuk :attribute tidak valid.',
    'regex' => 'Format isian :attribute tidak valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'mimes' => 'Kolom :attribute harus berupa file dengan tipe: :values.',
    'exists' => ':attribute yang dipilih tidak valid atau tidak ditemukan.',
    'required_without' => 'Kolom :attribute wajib diisi jika :values tidak diisi.',
    'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
    'after_or_equal' => 'Kolom :attribute harus berupa tanggal setelah atau sama dengan :date.',

    /*
    |--------------------------------------------------------------------------
    | Atribut Validasi Kustom
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'Nama',
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Password',
        'role' => 'Hak Akses',
        'nip' => 'NIP',
        'no_wa' => 'No. WhatsApp',
        'file' => 'File',
        'nip_guru' => 'NIP Guru',
        'username_guru' => 'Username Guru',
        'nama_guru' => 'Nama Guru',
        'kelas' => 'Kelas',
        'hari' => 'Hari',
        'jam_ke' => 'Jam Ke',
        'tipe_blok' => 'Tipe Blok',
        'tanggal_mulai' => 'Tanggal Mulai',
        'tanggal_selesai' => 'Tanggal Selesai',
        'keterangan' => 'Keterangan',
    ],

];

