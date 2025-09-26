<table>
    <thead>
        <tr>
            <th style="font-weight: bold;">Nama Guru</th>

            @foreach ($tanggalRange as $tanggal)
                <th style="font-weight: bold; text-align: center;">
                    {{ $tanggal->isoFormat('D MMM') }}
                </th>
            @endforeach

            <th style="font-weight: bold; background-color: #d3d3d3; text-align: center;">Total Absen</th>
        </tr>
    </thead>
    <tbody>

        @foreach ($semuaGuru as $guru)
            <tr>
                <td>{{ $guru->nama_guru }}</td>

                @foreach ($tanggalRange as $tanggal)
                    @php
                        $laporan = $guru->laporanHarian->firstWhere('tanggal', $tanggal->toDateString());
                        $status = $laporan ? substr($laporan->status, 0, 1) : '-';
                    @endphp
                    <td style="text-align: center;">
                        {{ $status }}
                    </td>
                @endforeach

                <td style="text-align: center;">
                    H:{{ $guru->laporanHarian->where('status', 'Hadir')->count() }} | S:{{ $guru->laporanHarian->where('status', 'Sakit')->count() }} | I:{{ $guru->laporanHarian->where('status', 'Izin')->count() }} | A:{{ $guru->laporanHarian->where('status', 'Alpa')->count() }}
                </td>
            </tr>
        @endforeach

    </tbody>
</table>