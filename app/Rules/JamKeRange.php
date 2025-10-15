<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class JamKeRange implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Pisahkan string berdasarkan koma, misal "1,2,23" -> [1, 2, 23]
        $jamArray = explode(',', $value);

        foreach ($jamArray as $jam) {
            // Hapus spasi dan ubah menjadi angka
            $jamAngka = (int) trim($jam);

            // Cek apakah setiap jam berada di antara 1 dan 10
            if ($jamAngka < 1 || $jamAngka > 10) {
                // Jika ada satu saja yang gagal, hentikan validasi dan kirim pesan error
                $fail('Kolom :attribute berisi jam pelajaran yang tidak valid. Gunakan angka antara 1-10.');
                return; // Hentikan pengecekan
            }
        }
    }
}