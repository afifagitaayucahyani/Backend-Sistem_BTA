<!DOCTYPE html>
<html>
<head>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Hasil Kelulusan BTA</h2>
        <p>Tanggal: {{ $tanggal }} | Fakultas: {{ $filter_fakultas }} | Prodi: {{ $filter_prodi }}</p>
    </div>
    <table>
        <tr>
            <th>NIM</th><th>Nama</th><th>Prodi</th><th>Kelas</th><th>Nilai</th><th>Mutu</th><th>Status</th>
        </tr>
        @foreach($data as $row)
        <tr>
            <td>{{ $row->mahasiswa->nim }}</td>
            <td>{{ $row->mahasiswa->user->name }}</td>
            <td>{{ $row->mahasiswa->program_studi }}</td>
            <td>{{ $row->kelas->nama_kelas }}</td>
            <td>{{ $row->total_poin }}</td>
            <td>{{ $row->huruf_mutu }}</td>
            <td>{{ $row->status_kelulusan ? 'LULUS' : 'GAGAL' }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>