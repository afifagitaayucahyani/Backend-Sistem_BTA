<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 0;
            /* Mengambil gambar yang diupload admin dari folder storage */
            background-image: url('{{ $template_base64 }}');
            background-size: cover;
            background-repeat: no-repeat;
            font-family: 'Arial', sans-serif;
        }

        /* Mengatur posisi teks menggunakan koordinat (Atas dan Kiri) */
        .nama-mahasiswa {
            position: absolute;
            top: 450px; /* Jarak dari atas kertas */
            width: 100%;
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #000;
        }

        .nomor-sertifikat {
            position: absolute;
            top: 200px;
            left: 100px; /* Jarak dari kiri kertas */
            font-size: 16px;
        }
        
        /* ... CSS posisi untuk NIM, Nilai, dll ... */
    </style>
</head>
<body>
    <div class="nomor-sk">Nomor: {{ $nomor_sk }}</div>
    <div class="nama-mahasiswa">{{ $nama_mahasiswa }}</div>
</body>
</html>