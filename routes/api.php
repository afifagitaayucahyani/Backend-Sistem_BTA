<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdministrasiController;
use App\Http\Controllers\TesPenempatanController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\SertifikatController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\PeriodeAkademikController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/login', function(){
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

    // rute untuk kepala pusat
    Route::middleware('role:Kepala Pusat')->group(function () {
        Route::put('/admin/nilai/sahkan/{kelas_id}', [NilaiController::class, 'sahkanNilai']);
    });

    // rute untuk mahasiswa
    Route::middleware('role:Mahasiswa')->group(function () {
        Route::post('/mahasiswa/slip-pembayaran', [AdministrasiController::class, 'uploadSlip']);
        Route::get('/mahasiswa/status-kelulusan', [SertifikatController::class, 'cekStatusKelulusan']);
        Route::get('/mahasiswa/sertifikat/download', [SertifikatController::class, 'downloadSertifikat']);
    });

    // rute mahasiswa dan tutor
    Route::middleware('role:Mahasiswa|Tutor')->group(function () {
        Route::post('/absensi/hadir', [AbsensiController::class, 'hadir']);
    });


    // rute untuk tutor
    Route::middleware('role:Tutor')->group(function () {
         Route::get('/tutor/kelas-ku', [KelasController::class, 'kelasKu']);
        Route::put('/absensi/{id}/anulir', [AbsensiController::class, 'anulirKehadiran']);
        Route::get('/tutor/nilai/template/{kelas_id}', [NilaiController::class, 'downloadTemplate']);
        Route::post('/tutor/nilai/upload', [NilaiController::class, 'uploadNilai']);
    });

    // rute untuk admin
    Route::middleware('role:Admin')->group(function () {
        Route::post('/admin/tes-penempatan/input-nilai', [TesPenempatanController::class, 'inputNilai']);
        Route::get('/admin/antrean-slip', [AdministrasiController::class, 'getDaftarAntrean']);
        Route::put('/admin/validasi-slip/{id}', [AdministrasiController::class, 'validasiSlip']);
        Route::get('/admin/nilai/antrean', [NilaiController::class, 'getAntreanValidasi']);
        Route::put('/admin/nilai/validasi-staff/{kelas_id}', [NilaiController::class, 'validasiTahapSatu']);
        Route::post('/admin/sertifikat/template', [SertifikatController::class, 'uploadTemplate']);
        Route::post('/admin/kelas', [KelasController::class, 'store']);
        Route::get('/admin/kelas', [KelasController::class, 'index']);
        Route::get('/kelas/{id}/detail', [KelasController::class, 'detailKelas']); 
        Route::post('/admin/kelas/{id}/plot-tutor', [KelasController::class, 'plotTutor']);
        Route::post('/admin/kelas/{id}/tambah-peserta', [KelasController::class, 'tambahPeserta']);
        // data mahasiswa
         Route::get('/admin/mahasiswa', [MahasiswaController::class, 'index']);               
        Route::post('/admin/mahasiswa', [MahasiswaController::class, 'store']);  
        Route::put('/admin/mahasiswa/{id}', [MahasiswaController::class, 'update']);       
        Route::delete('/admin/mahasiswa/{id}', [MahasiswaController::class, 'destroy']); 
        // periode  akademik
        Route::get('/admin/periode', [PeriodeAkademikController::class, 'index']);
        Route::post('/admin/periode', [PeriodeAkademikController::class, 'store']);
        // aktif periode
        Route::patch('/admin/periode/{id}/set-active', [PeriodeAkademikController::class, 'setActive']);
    });

    // rute untuk admin dan kepala pusat
    Route::middleware('role:Admin|Kepala Pusat')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
    });


    // rute untuk rektorat dan kepala pusat
    Route::middleware('role:Rektorat|Kepala Pusat')->group(function () {
        Route::get('/admin/laporan/statistik', [LaporanController::class, 'statistikDashboard']);
        Route::get('/admin/laporan/akademik', [LaporanController::class, 'laporanAkademik']);
        Route::get('/admin/laporan/export', [LaporanController::class, 'exportLaporan']);
    });
});
