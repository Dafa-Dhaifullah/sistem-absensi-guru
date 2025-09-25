<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Jadwal Pelajaran dari Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            {!! session('error') !!}
                        </div>
                    @endif

                    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-bold">Petunjuk Pengisian</h3>
                        <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
                            <li>Download template Excel yang sudah disediakan.</li>
                            <li>Isi data sesuai format. Pastikan nama header tidak diubah.</li>
                            <li>Kolom **nip_guru** isi dengan NIP yang sudah terdaftar di Data Guru.</li>
                            <li>Kolom **nama_guru** boleh diisi Jika nip kosong  </li>
                            <li>Untuk **jam_ke** yang lebih dari satu jam (misal: jam ke 1 dan 2), tulis dengan koma tanpa spasi: `1,2`.</li>
                            <li>Nilai **hari** harus: Senin, Selasa, Rabu, Kamis, Jumat, atau Sabtu.</li>
                            <li>Nilai **tipe_blok** harus: Setiap Minggu, Hanya Minggu 1, atau Hanya Minggu 2.</li>
                        </ul>
                        <div class="mt-4">
                            <a href="{{ asset('templates/template_jadwal.xlsx') }}" download
                               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700">
                               Download Template
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('admin.jadwal-pelajaran.import.excel') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <x-input-label for="file" :value="__('Pilih File Excel (.xlsx, .xls)')" />
                            <x-text-input id="file" class="block mt-1 w-full border p-2" type="file" name="file" required />
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Upload dan Import') }}</x-primary-button>
                            <a href="{{ route('admin.jadwal-pelajaran.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>