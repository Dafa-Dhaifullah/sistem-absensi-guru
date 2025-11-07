<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use App\Imports\JadwalPelajaranImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\MasterHariKerja;
use App\Rules\JamKeRange;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;


class JadwalPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
         $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        $query = JadwalPelajaran::with('user');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kelas', 'like', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }
        $semuaJadwal = $query->latest()->paginate(15);
        $semuaJadwal->appends($request->query());
        return view('admin.jadwal_pelajaran.index', compact('semuaJadwal', 'daftarGuru', 'hariAktif'));
    }

    public function create()
    {
        $daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        
        return view('admin.jadwal_pelajaran.create', [
            'daftarGuru' => $daftarGuru,
            'hariAktif' => $hariAktif // <-- 4. KIRIM KE VIEW
        ]);
    }

  public function store(Request $request)
    {
         $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => ['required', Rule::in($hariAktif)],
            'jam_ke' => 'required|array',
            'jam_ke.*' => 'required|integer|min:1|max:10',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ]);

        $jamKeArray = $validatedData['jam_ke'];
        $konflik = [];
        $tipeBlokBaru = $validatedData['tipe_blok'];

        foreach ($jamKeArray as $jam) {
            // --- Logika Pengecekan Konflik yang Sudah Diperbaiki ---
            $query = JadwalPelajaran::where('hari', $validatedData['hari'])->where('jam_ke', $jam);

            // Tambahkan logika filter blok yang tumpang tindih
            $query->where(function ($q) use ($tipeBlokBaru) {
                $q->where('tipe_blok', 'Setiap Minggu');
                if ($tipeBlokBaru === 'Setiap Minggu') {
                    $q->orWhereIn('tipe_blok', ['Hanya Minggu 1', 'Hanya Minggu 2']);
                } else {
                    $q->orWhere('tipe_blok', $tipeBlokBaru);
                }
            });

            // Cek apakah ada konflik KELAS atau GURU pada slot waktu yang sama
            $konflikJadwal = $query->where(function ($q) use ($validatedData) {
                $q->where('kelas', $validatedData['kelas'])
                  ->orWhere('user_id', $validatedData['user_id']);
            })->with('user')->first();

            if ($konflikJadwal) {
                // Tentukan pesan error berdasarkan jenis konflik
                if ($konflikJadwal->kelas === $validatedData['kelas']) {
                    $konflik[] = "Jam ke-{$jam} di kelas {$validatedData['kelas']} sudah diisi oleh {$konflikJadwal->user->name} (Blok: {$konflikJadwal->tipe_blok}).";
                } else {
                    $konflik[] = "Guru yang dipilih sudah memiliki jadwal lain di kelas {$konflikJadwal->kelas} pada jam ke-{$jam} (Blok: {$konflikJadwal->tipe_blok}).";
                }
            }
        }

        if (!empty($konflik)) {
            return redirect()->back()->withInput()->withErrors(array_unique($konflik));
        }
        
        unset($validatedData['jam_ke']);
        foreach ($jamKeArray as $jam) {
            $dataToCreate = $validatedData;
            $dataToCreate['jam_ke'] = $jam; 
            JadwalPelajaran::create($dataToCreate);
        }

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    /**
     * Mengupdate jadwal pelajaran dengan validasi konflik yang disempurnakan.
     */
    public function update(Request $request, JadwalPelajaran $jadwalPelajaran)
    {
         $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => ['required', Rule::in($hariAktif)],
            'jam_ke' => 'required|integer|min:1|max:10',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ]);
        
        $konflik = [];
        $tipeBlokBaru = $validatedData['tipe_blok'];
        $jam = $validatedData['jam_ke'];

        $query = JadwalPelajaran::where('id', '!=', $jadwalPelajaran->id)
            ->where('hari', $validatedData['hari'])
            ->where('jam_ke', $jam);
        
        $query->where(function ($q) use ($tipeBlokBaru) {
            $q->where('tipe_blok', 'Setiap Minggu');
            if ($tipeBlokBaru === 'Setiap Minggu') {
                $q->orWhereIn('tipe_blok', ['Hanya Minggu 1', 'Hanya Minggu 2']);
            } else {
                $q->orWhere('tipe_blok', $tipeBlokBaru);
            }
        });

        $konflikJadwal = $query->where(function ($q) use ($validatedData) {
            $q->where('kelas', $validatedData['kelas'])
              ->orWhere('user_id', $validatedData['user_id']);
        })->with('user')->first();

        if ($konflikJadwal) {
            if ($konflikJadwal->kelas === $validatedData['kelas']) {
                $konflik[] = "Jam ke-{$jam} di kelas {$validatedData['kelas']} sudah diisi oleh {$konflikJadwal->user->name} (Blok: {$konflikJadwal->tipe_blok}).";
            } else {
                $konflik[] = "Guru yang dipilih sudah memiliki jadwal lain di kelas {$konflikJadwal->kelas} pada jam ke-{$jam} (Blok: {$konflikJadwal->tipe_blok}).";
            }
        }
        
        if (!empty($konflik)) {
            return redirect()->back()->withInput()->withErrors(array_unique($konflik));
        }

        $jadwalPelajaran->update($validatedData);

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function edit(JadwalPelajaran $jadwalPelajaran)
    {
        $daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
         $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        return view('admin.jadwal_pelajaran.edit', [
            'jadwal' => $jadwalPelajaran,
            'daftarGuru' => $daftarGuru,
            'hariAktif' => $hariAktif
        ]);
    }

    

    

    public function destroy(JadwalPelajaran $jadwalPelajaran)
    {
        $jadwalPelajaran->delete();
        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
    
    public function showImportForm()
    {
        return view('admin.jadwal_pelajaran.import');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $rows = Excel::toCollection(new JadwalPelajaranImport, $request->file('file'))->first();

        $errors = [];
        $validatedData = [];
        $jadwalStaging = collect(); // "Staging area" untuk data yang akan diimpor

        $guruByNip = User::where('role', 'guru')->whereNotNull('nip')->pluck('id', 'nip');
        $guruByUsername = User::where('role', 'guru')->pluck('id', 'username');

        $hariAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        // === TAHAP 1: VALIDASI FORMAT SETIAP BARIS ===
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $validator = Validator::make($row->toArray(), [
                'nip_guru' => ['nullable', Rule::exists('users', 'nip')->where('role', 'guru')],
                'username_guru' => ['required_without:nip_guru', Rule::exists('users', 'username')->where('role', 'guru')],
                'kelas' => 'required|string',
                'hari' => ['required', Rule::in($hariAktif)],
                'jam_ke' => ['required', new JamKeRange],
                'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $errors[] = "Baris {$rowNumber}: {$error}";
                }
                continue; // Lanjut ke baris berikutnya jika format sudah salah
            }

            // Jika format benar, siapkan data untuk validasi konflik
            $userId = !empty($row['nip_guru']) ? $guruByNip->get($row['nip_guru']) : $guruByUsername->get($row['username_guru']);
            $jamKeArray = explode(',', strval($row['jam_ke']));
            foreach ($jamKeArray as $jam) {
                $validatedData[] = [
                    'row_number' => $rowNumber,
                    'user_id' => $userId,
                    'hari' => $row['hari'],
                    'jam_ke' => (int) trim($jam),
                    'kelas' => $row['kelas'],
                    'mata_pelajaran' => $row['mata_pelajaran'],
                    'tipe_blok' => $row['tipe_blok'],
                ];
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->with('import_errors', $errors);
        }

        // === TAHAP 2: VALIDASI KONFLIK LINTAS-BARIS DAN DENGAN DATABASE ===
        foreach ($validatedData as $data) {
            // Cek konflik dengan data yang SUDAH ADA di database
            $konflikDb = JadwalPelajaran::where('hari', $data['hari'])
                ->where('jam_ke', $data['jam_ke'])
                ->where(function ($q) use ($data) {
                    $q->where('kelas', $data['kelas'])
                      ->orWhere('user_id', $data['user_id']);
                })->get();

            foreach ($konflikDb as $dbRow) {
                if ($this->isConflict($data['tipe_blok'], $dbRow->tipe_blok)) {
                    $errors[] = "Baris {$data['row_number']}: Konflik dengan data di database (Kelas/Guru pada jam yang sama).";
                }
            }

            // Cek konflik dengan data LAIN di dalam file Excel ini sendiri
            $konflikStaging = $jadwalStaging->where('hari', $data['hari'])
                ->where('jam_ke', $data['jam_ke'])
                ->filter(function($stg) use ($data) {
                    return $this->isConflict($data['tipe_blok'], $stg['tipe_blok']) &&
                           ($stg['kelas'] == $data['kelas'] || $stg['user_id'] == $data['user_id']);
                });
            
            if ($konflikStaging->isNotEmpty()) {
                 $errors[] = "Baris {$data['row_number']}: Konflik dengan baris lain di dalam file Excel yang sama.";
            }

            $jadwalStaging->push($data);
        }

        if (!empty($errors)) {
            return redirect()->back()->with('import_errors', array_unique($errors));
        }

        // === TAHAP 3: SIMPAN DATA JIKA TIDAK ADA ERROR SAMA SEKALI ===
        foreach($validatedData as $data) {
            JadwalPelajaran::create([
                'user_id' => $data['user_id'],
                'hari' => $data['hari'],
                'jam_ke' => $data['jam_ke'],
                'kelas' => $data['kelas'],
                'mata_pelajaran' => $data['mata_pelajaran'],
                'tipe_blok' => $data['tipe_blok'],
            ]);
        }

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', count($validatedData) . ' data jadwal berhasil diimpor!');
    }
    
    private function isConflict($newBlock, $existingBlock)
    {
        return $newBlock === 'Setiap Minggu' || $existingBlock === 'Setiap Minggu' || $newBlock === $existingBlock;
    }
}