<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\JadwalPelajaran;
use App\Models\User;

class NoScheduleConflict implements ValidationRule, DataAwareRule
{
    protected $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hari = $this->data['hari'] ?? null;
        $kelas = $this->data['kelas'] ?? null;
        $tipeBlokBaru = $this->data['tipe_blok'] ?? null;
        $nip = $this->data['nip_guru'] ?? null;
        $username = $this->data['username_guru'] ?? null;
        
        if (!$hari || !$kelas || !$value || !$tipeBlokBaru) {
            return; // Lewati jika data dasar tidak ada
        }

        $user = null;
        if ($nip) {
            $user = User::where('nip', $nip)->first();
        } elseif ($username) {
            $user = User::where('username', $username)->first();
        }

        if (!$user) {
            return; // Lewati jika guru tidak ditemukan (sudah divalidasi oleh 'exists')
        }

        $jamKeArray = explode(',', $value);

        foreach ($jamKeArray as $jam) {
            $jam = (int) trim($jam);

            // Cek konflik kelas
            $jadwalKelasLain = JadwalPelajaran::where('hari', $hari)->where('jam_ke', $jam)->where('kelas', $kelas)->get();
            foreach ($jadwalKelasLain as $jadwalLain) {
                if ($this->isConflict($tipeBlokBaru, $jadwalLain->tipe_blok)) {
                    $fail("Konflik Kelas di jam ke-{$jam}: Kelas {$kelas} sudah diisi oleh {$jadwalLain->user->name} (Blok: {$jadwalLain->tipe_blok}).");
                    return;
                }
            }

            // Cek konflik guru
            $jadwalGuruLain = JadwalPelajaran::where('hari', $hari)->where('jam_ke', $jam)->where('user_id', $user->id)->get();
            foreach ($jadwalGuruLain as $jadwalLain) {
                if ($this->isConflict($tipeBlokBaru, $jadwalLain->tipe_blok)) {
                    $fail("Konflik Guru di jam ke-{$jam}: Guru {$user->name} sudah memiliki jadwal lain di kelas {$jadwalLain->kelas} (Blok: {$jadwalLain->tipe_blok}).");
                    return;
                }
            }
        }
    }

    private function isConflict($newBlock, $existingBlock)
    {
        return $newBlock === 'Setiap Minggu' || $existingBlock === 'Setiap Minggu' || $newBlock === $existingBlock;
    }
}