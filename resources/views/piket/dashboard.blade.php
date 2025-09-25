<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dasbor Guru Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            <div class="font-medium">Whoops! Ada yang salah.</div>
                            <ul class="mt-3 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('piket.laporan-harian.store') }}" method="POST">
                        @csrf
                        
                        <h3 class="text-lg font-medium">
                            Daftar Guru Wajib Hadir Hari Ini
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Hari: <span class="font-semibold">{{ $hariIni ?? 'N/A' }}</span> | 
                            Tipe Minggu: <span class="font-semibold">{{ $tipeMinggu ?? 'N/A' }}</span>
                        </p>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Guru</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kehadiran</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
    @forelse ($guruWajibHadir as $guru)
        <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ $guru->nama_guru }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                
                @php
                    // Cek status tersimpan HARI INI (dari controller)
                    $laporan = $laporanHariIni->get($guru->id);
                    // Ini sudah benar (menggunakan : null)
                    $statusTersimpan = $laporan ? $laporan->status : null;
                @endphp

                <select name="status_guru[{{ $guru->id }}]" 
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    
                    <option value="" @if(!$statusTersimpan) selected @endif>-- Belum Diabsen --</option>
                    
                    <option value="Hadir" @if($statusTersimpan == 'Hadir') selected @endif>Hadir</option>
                    <option value="Sakit" @if($statusTersimpan == 'Sakit') selected @endif>Sakit</option>
                    <option value="Izin" @if($statusTersimpan == 'Izin') selected @endif>Izin</option>
                    <option value="DL" @if($statusTersimpan == 'DL') selected @endif>Dinas Luar (DL)</option>
                    <option value="Alpa" @if($statusTersimpan == 'Alpa') selected @endif>Alpa</option>
                
                </select>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                Tidak ada guru yang terjadwal hari ini.
            </td>
        </tr>
    @endforelse
</tbody>
                            </table>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-lg font-medium">Logbook Kejadian Harian (Opsional)</h3>
                            
                            <div class="mt-4">
                                <x-input-label for="kejadian_penting" :value="__('Kejadian Penting Hari Ini')" />
                                <textarea id="kejadian_penting" name="kejadian_penting" rows="4" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('kejadian_penting') }}</textarea>
                            </div>
                            
                            <div class="mt-4">
                                <x-input-label for="tindak_lanjut" :value="__('Tindak Lanjut Penyelesaiannya')" />
                                <textarea id="tindak_lanjut" name="tindak_lanjut" rows="4" 
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('tindak_lanjut') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            
                            <x-primary-button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyimpan laporan harian ini? Data tidak dapat diubah setelah disimpan.');">
                                {{ __('Simpan & Kunci Laporan Harian') }}
                            </x-primary-button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>