<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NilaiAkhir;
use App\Models\PendaftaranBta;
use App\Models\PesertaKelas;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    // laporan statistik
    public function statistikDashboard()
    {
        // A. Statistik Akademik Kelulusan
        $totalLulus = NilaiAkhir::where('status_kelulusan', true)->count();
        $totalGagal = NilaiAkhir::where('status_kelulusan', false)
                                ->where('status_validasi', 'Disahkan Pusat') // Hanya hitung yang sudah final
                                ->count();

        // B. Keuangan (Asumsi biaya pendaftaran Rp 100.000 / Mahasiswa)
        $pendaftarValid = PendaftaranBta::where('status_validasi', 'Valid')->count();
        $totalPendapatan = $pendaftarValid * 100000;

        // C. Tren Kelas (MQ 1 vs MQ 2) 
        $totalMq1 = PesertaKelas::whereHas('kelas', function ($query) {
            $query->where('tingkat', 'Menengah');
        })->count();

        $totalMq2 = PesertaKelas::whereHas('kelas', function ($query) {
            $query->where('tingkat', 'Mahir');
        })->count();

        return response()->json([
            'message' => 'Data statistik dashboard berhasil diambil.',
            'data'    => [
                'akademik' => [
                    'lulus' => $totalLulus,
                    'gagal' => $totalGagal,
                    'total_dinilai' => $totalLulus + $totalGagal
                ],
                'keuangan' => [
                    'pendaftar_valid'  => $pendaftarValid,
                    'total_pendapatan' => $totalPendapatan
                ],
                'distribusi_kelas' => [
                    'mq_1_menengah' => $totalMq1,
                    'mq_2_mahir'    => $totalMq2
                ]
            ]
        ], 200);
    }

    // tabel nilai dengan fitur filter
    public function laporanAkademik(Request $request)
    {
        // Mulai merangkai Query (Hanya ambil data yang sudah final disahkan)
        $query = NilaiAkhir::with(['mahasiswa.user:id,name,email', 'kelas:id,nama_kelas,tingkat'])
                           ->where('status_validasi', 'Disahkan Pusat');

        // Filter dinamis berdasarkan Fakultas (jika dikirim dari React)
        if ($request->filled('fakultas')) {
            $query->whereHas('mahasiswa', function ($q) use ($request) {
                $q->where('fakultas', $request->fakultas);
            });
        }

        // Filter dinamis berdasarkan Program Studi 
        if ($request->filled('program_studi')) {
            $query->whereHas('mahasiswa', function ($q) use ($request) {
                $q->where('program_studi', $request->program_studi);
            });
        }

        // Eksekusi query
        $laporan = $query->latest()->get();

        return response()->json([
            'message' => 'Data laporan akademik berhasil diambil.',
            'total_data' => $laporan->count(),
            'data'    => $laporan
        ], 200);
    }

    // unduh data (format pdf dan csv)
    public function exportLaporan(Request $request)
    {
        $format = $request->query('format', 'csv'); 

        // Replikasi logika filter dari laporanAkademik
        $query = NilaiAkhir::with(['mahasiswa.user', 'kelas'])
                           ->where('status_validasi', 2);

        if ($request->filled('fakultas')) {
            $query->whereHas('mahasiswa', function ($q) use ($request) {
                $q->where('fakultas', $request->fakultas);
            });
        }
        if ($request->filled('program_studi')) {
            $query->whereHas('mahasiswa', function ($q) use ($request) {
                $q->where('program_studi', $request->program_studi);
            });
        }

        $dataLaporan = $query->get();
        $tanggalExport = now()->translatedFormat('d F Y');

        // --- JIKA REQUEST FORMAT PDF ---
        if ($format === 'pdf') {
            
            $pdf = Pdf::loadView('pdf.laporan_akademik', [
                'data' => $dataLaporan,
                'tanggal' => $tanggalExport,
                'filter_fakultas' => $request->fakultas ?? 'Semua',
                'filter_prodi' => $request->program_studi ?? 'Semua',
            ])->setPaper('a4', 'landscape'); 

            return $pdf->download('Laporan_Akademik_BTA_' . time() . '.pdf');
        }

        // --- JIKA REQUEST FORMAT CSV (EXCEL) ---
        $csvData = "NIM,Nama Mahasiswa,Fakultas,Program Studi,Kelas,Tingkat,Nilai Total,Huruf Mutu,Status Kelulusan\n";
        
        foreach ($dataLaporan as $row) {
            $status = $row->status_kelulusan ? 'LULUS' : 'TIDAK LULUS';
            $csvData .= "\"{$row->mahasiswa->nim}\",\"{$row->mahasiswa->user->name}\",\"{$row->mahasiswa->fakultas}\",\"{$row->mahasiswa->program_studi}\",\"{$row->kelas->nama_kelas}\",\"{$row->kelas->tingkat}\",\"{$row->total_poin}\",\"{$row->huruf_mutu}\",\"{$status}\"\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="Laporan_Akademik_BTA_' . time() . '.csv"');
    }

}
