<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::get('/', 'HomeController');
Route::group(['middleware' => ['guest:api']], function () {
    Route::post('login', 'Auth\LoginController@login');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');
    Route::get('/user', 'Settings\ProfileController@index');
    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');
    Route::group(['prefix' => 'sample'], function () {
        Route::get('/get-data', 'SampleController@getData');
        Route::post('/add', 'SampleController@add');
        Route::get('/get/{id}', 'SampleController@getById');
        Route::get('/edit/{id}', 'SampleController@getUpdate');
        Route::delete('/delete/{id}', 'SampleController@delete');
        Route::post('/update/{id}', 'SampleController@storeUpdate');
        Route::get('/get-sample/{nomor}', 'SampleController@getSamples');
    });

    Route::get('/pengguna', 'PenggunaController@listPengguna');
    Route::post('/pengguna', 'PenggunaController@savePengguna');
    Route::post('/pengguna/{id}', 'PenggunaController@updatePengguna');
    Route::delete('/pengguna/{id}', 'PenggunaController@deletePengguna');
    Route::get('/pengguna/{id}', 'PenggunaController@showUpdate');
    Route::get('/kota', 'KotaController@listKota');
    Route::get('/provinsi', 'KotaController@listProvinsi');
    Route::get('/kecamatan', 'KotaController@listKecamatan');
    Route::get('/kelurahan', 'KotaController@listKelurahan');
    Route::post('/kota', 'KotaController@saveKota');
    Route::post('/kota/{id}', 'KotaController@updateKota');
    Route::delete('/kota/{id}', 'KotaController@deleteKota');
    Route::get('/kota/{id}', 'KotaController@showUpdate');
    Route::get('/dinkes', 'DinkesController@listDinkes');
    Route::post('/dinkes', 'DinkesController@saveDinkes');
    Route::post('/dinkes/{id}', 'DinkesController@updateDinkes');
    Route::delete('/dinkes/{id}', 'DinkesController@deleteDinkes');
    Route::get('/dinkes/{id}', 'DinkesController@showUpdate');
    Route::get('roles-option', 'OptionController@getRoles');
    Route::get('lab-pcr-option', 'OptionController@getLabPCR');
    Route::get('lab-satelit-option', 'OptionController@getLabSatelit');
    Route::get('jenis-sampel-option', 'OptionController@getJenisSampel');
    Route::get('jenis-vtm', 'OptionController@getJenisVTM');
    Route::get('validator-option', 'OptionController@getValidator');
    Route::group(['prefix' => 'registrasi-mandiri'], function () {
        Route::get('/', 'RegistrasiMandiri@getData');
        Route::get('/export-excel', 'RegistrasiMandiri@exportMandiri')->middleware('can:isAdmin');
    });

    Route::group(['prefix' => 'registrasi-rujukan'], function () {
        Route::post('/cek', 'RegistrasiRujukanController@cekData');
        Route::post('/store', 'RegistrasiRujukanController@store');
        Route::get('/export-excel-rujukan', 'RegistrasiMandiri@exportRujukan')->middleware('can:isAdmin');
        Route::delete('/delete/{id}/{pasien}', 'RegistrasiRujukanController@delete');
        Route::post('update/{register_ids}/{pasien_id}', 'RegistrasiRujukanController@storeUpdate');
        Route::get('update/{register_id}/{pasien_id}', 'RegistrasiRujukanController@getById');
        Route::get('/export-excel', 'RegistrasiRujukanController@exportExcel');
    });

    Route::group(['prefix' => 'pemeriksaansampel'], function () {
        Route::get('/get-data', 'PemeriksaanSampleController@getData');
        Route::get('/get-dikirim', 'PemeriksaanSampleController@getDikirim');
    });
    // temp Routing for migration
    Route::prefix('migrasi')->group(function () {
        Route::post('/mandiri', 'Temp\MigrasiController@migrasiMandiri');
        Route::post('rujukan', 'Temp\MigrasiController@MigrasiRujukan');
    });
    //V1
    Route::group(['namespace' => 'V1', 'prefix' => 'v1'], function () {
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/tracking', 'DashboardController@tracking');
            Route::get('/ekstraksi', 'DashboardController@ekstraksi');
            Route::get('/registrasi', 'DashboardController@registrasi');
            Route::get('/pcr', 'DashboardController@pcr');
            Route::get('/notifications', 'DashboardController@notifications');
            Route::get('/positif-negatif', 'DashboardController@positifNegatif');
            Route::get('sampel', 'DashboardController@sampel');
            Route::get('counter-belum-verifikasi', 'DashboardVerifikasiController@getCountUnverify');
            Route::get('counter-terverifikasi', 'DashboardVerifikasiController@getCountVerified');
            Route::get('counter-belum-validasi', 'DashboardValidasiController@getCountUnvalidate');
            Route::get('counter-tervalidasi', 'DashboardValidasiController@getCountValidated');
        });

        Route::group(['prefix' => 'chart'], function () {
            Route::get('/regis-mandiri', 'DashboardController@chartMandiri');
            Route::get('/regis-rujukan', 'DashboardController@chartRujukan');
            Route::get('/positif', 'DashboardController@chartPositif');
            Route::get('/negatif', 'DashboardController@chartNegatif');
            Route::get('/ekstraksi', 'DashboardController@chartEkstraksi');
            Route::get('/pcr', 'DashboardController@chartPcr');
        });

        Route::group(['prefix' => 'ekstraksi'], function () {
            Route::get('/get-data', 'EkstraksiController@getData');
            Route::get('/detail/{id}', 'EkstraksiController@detail');
            Route::post('/edit/{sampel}', 'EkstraksiController@edit');
            Route::post('/set-invalid/{id}', 'EkstraksiController@setInvalid');
            Route::post('/set-proses/{id}', 'EkstraksiController@setProses');
            Route::post('/terima', 'EkstraksiController@terima');
            Route::post('/kirim', 'EkstraksiController@kirim');
            Route::post('/kirim-ulang', 'EkstraksiController@kirimUlang');
            Route::post('/musnahkan/{id}', 'EkstraksiController@musnahkan');
            Route::post('/set-kurang/{id}', 'EkstraksiController@setKurang');
            Route::post('/set-swab-ulang/{id}', 'EkstraksiController@setSwabUlang');
        });

        Route::group(['prefix' => 'pcr'], function () {
            Route::get('/get-data', 'PCRController@getData');
            Route::get('/detail/{id}', 'PCRController@detail');
            Route::post('/edit/{id}', 'PCRController@edit');
            Route::post('/terima', 'PCRController@terima');
            Route::post('/invalid/{id}', 'PCRController@invalid');
            Route::post('/input/{id}', 'PCRController@input');
            Route::post('/upload-grafik', 'PCRController@uploadGrafik');
            Route::post('/musnahkan/{id}', 'PCRController@musnahkan');
            Route::post('/import-hasil-pemeriksaan', 'PCRController@importHasilPemeriksaan');
            Route::post('/import-data-hasil-pemeriksaan', 'PCRController@importDataHasilPemeriksaan');
        });

        Route::group(['prefix' => 'sampel'], function () {
            Route::get('/cek-nomor-sampel', 'SampelController@cekNomorSampel');
        });

        Route::get('list-kota-jabar', 'KotaController@listKota');
        Route::get('list-kota-all', 'KotaController@listKotaAll');
        Route::get('list-kecamatan/{kota}', 'KotaController@listKecamatan');
        Route::get('list-kelurahan/{kec}', 'KotaController@listKelurahan');
        Route::get('kota/detail/{kota}', 'KotaController@show');
        Route::get('list-fasyankes-jabar', 'FasyankesController@listByProvinsi');
        Route::get('fasyankes/detail/{fasyankes}', 'FasyankesController@show');
        Route::get('list-gejala', 'GejalaController@getListMasterGejala');
        Route::get('gejala/detail/{gejala}', 'GejalaController@show');
        Route::get('list-penyakit-penyerta', 'PenyakitPenyertaController@getListMaster');
        Route::get('penyakit-penyerta/detail/{penyakitPenyerta}', 'PenyakitPenyertaController@show');
        Route::group(['prefix' => 'register'], function () {
            Route::get('/', 'RegisterListController@index');
            Route::post('store', 'RegisterController@store');
            Route::post('mandiri', 'RegisterController@storeMandiri');
            Route::post('mandiri/update/{regis_id}/{pasien_id}', 'RegisterController@storeUpdate');
            Route::get('mandiri/{register_id}/{pasien_id}', 'RegisterController@getById');
            Route::get('logs/{register_id}', 'RegisterController@logs');
            Route::delete('mandiri/{register_id}/{pasien_id}', 'RegisterController@delete');
            Route::get('delete-sampel/{id}', 'RegisterController@deleteSample');
            Route::get('detail/{register}', 'RegisterController@show');
            Route::post('update/{register}', 'RegisterController@update');
            Route::delete('delete/{register}', 'RegisterController@destroy');
            Route::get('noreg', 'RegisterController@generateNomorRegister');
            Route::get('get-noreg', 'RegisterController@requestNomor');
            Route::post('import-mandiri', 'ImportRegisterController@importRegisterMandiri');
            Route::post('import-rujukan', 'ImportRegisterController@importRegisterRujukan');
            Route::group(['prefix' => 'rujukan'], function () {
                Route::post('store', 'RegisterRujukanController@store');
                Route::get('detail/{register}', 'RegisterRujukanController@show');
                Route::post('update/{register}', 'RegisterRujukanController@update');
                Route::delete('delete/{register}', 'RegisterRujukanController@delete');
            });
        });

        Route::group(['prefix' => 'pengambilan-sampel'], function () {
            Route::get('list-dikirim', 'PengambilanListController@listDikirim');
            Route::get('list-sampel-register', 'PengambilanListController@listSampelRegister');
            Route::post('store', 'PengambilanSampelController@store');
            Route::post('update/{pengambilan}', 'PengambilanSampelController@update');
            Route::get('detail/{pengambilan}', 'PengambilanSampelController@show');
            Route::delete('delete/{pengambilan}', 'PengambilanSampelController@destroy');
            Route::delete('delete/sampel/{sampel}', 'PengambilanSampelController@destroySampel');
        });

        Route::group(['prefix' => 'sampel'], function () {
            Route::post('store', 'SampelController@store');
            Route::post('update/{sampel}', 'SampelController@update');
            Route::get('detail/{sampel}', 'SampelController@show');
            Route::get('barcode/{barcode}', 'SampelController@showByBarcode');
            Route::delete('delete/{sampel}', 'SampelController@destroy');
        });

        Route::group(['prefix' => 'verifikasi', 'middleware' => 'can:isAdminVerifikator'], function () {
            Route::get('list', 'VerifikasiController@index');
            Route::get('list-verified', 'VerifikasiController@indexVerified');
            Route::get('detail/{sampel}', 'VerifikasiController@show');
            Route::get('get-sampel-status', 'VerifikasiController@sampelStatusList');
            Route::post('edit-status-sampel/{sampel}', 'VerifikasiController@updateToVerified');
            Route::get('export-excel-verifikasi', 'VerifikasiController@exportVerifikasi');
            Route::get('export-excel-terverifikasi', 'VerifikasiController@exportTerverifikasi');
            Route::post('verifikasi-single-sampel/{sampel}', 'VerifikasiController@verifiedSingleSampel');
        });

        Route::group(['prefix' => 'verifikasi'], function () {
            Route::get('list-kategori', 'VerifikasiController@listKategori');
        });

        Route::group(['prefix' => 'validasi', 'middleware' => 'can:isAdminValidator'], function () {
            Route::get('list', 'ValidasiController@index');
            Route::get('list-validated', 'ValidasiController@indexValidated');
            Route::get('detail/{sampel}', 'ValidasiController@show');
            Route::post('edit-status-sampel/{sampel}', 'ValidasiController@updateToValidate');
            Route::get('list-validator', 'ValidasiController@getValidator');
            Route::get('export-pdf/{sampel}', 'ValidasiExportController@exportPDF');
            Route::post('bulk-validasi', 'ValidasiController@bulkValidate');
            Route::get('export-excel-validasi', 'ValidasiController@exportValidasi');
            Route::get('export-excel-tervalidasi', 'ValidasiController@exportTervalidasi');
            Route::post('regenerate-pdf/{sampel}', 'ValidasiController@regeneratePdfHasil');
            Route::post('reject-sample', 'ValidasiController@rejectSample');
        });

        Route::apiResource('validator', 'ValidatorController');
        Route::group(['prefix' => 'pelacakan-sampel'], function () {
            Route::get('list', 'PelacakanSampelController@index');
            Route::get('detail/{sampel}', 'PelacakanSampelController@show');
        });
        //pelaporan
        Route::group(['prefix' => 'pelaporan'], function () {
            Route::get('data', 'PelaporanController@fetchData');
        });
        Route::group(['prefix' => 'pasien'], function () {
            Route::get('data', 'PasienController@fetchData');
        });

        Route::group(['middleware' => 'can:isAdmin'], function () {
            Route::apiResource('jenis-vtm', 'JenisVTMController');
            Route::apiResource('reagen-ekstraksi', 'ReagenEkstraksiController');
            Route::apiResource('reagen-pcr', 'ReagenPCRController');
            Route::group(['prefix' => 'list'], function () {
                Route::get('reagen-ekstraksi', 'ReagenEkstraksiListController');
                Route::get('reagen-pcr', 'ReagenPCRListController');
            });
        });
        Route::group(['prefix' => 'tes-masif'], function () {
            Route::get('/', 'TesMasifController@index');
            Route::post('/bulk', 'TesMasifController@bulkTesMasif');
            Route::post('/registering', 'TesMasifController@bulk');
        });
    });
    //V2
    Route::group(['namespace' => 'V2', 'prefix' => 'v2'], function () {
        Route::group(['prefix' => 'ekstraksi'], function () {
            Route::get('/get-data', 'EkstraksiController@getData');
        });

        Route::group(['prefix' => 'pcr'], function () {
            Route::get('/get-data', 'PCRController@getData');
        });

        Route::group(['prefix' => 'pelacakan-sampel'], function () {
            Route::get('list', 'PelacakanSampelController@index');
        });

        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/tracking-progress', 'DashboardController@trackingProgress');
            Route::get('/positif-negatif', 'DashboardController@positifNegatif');
            Route::get('/pasien-diperiksa', 'DashboardController@pasienDiperiksa');
            Route::get('/registrasi', 'DashboardController@registrasi');
            Route::get('/admin-sampel', 'DashboardController@adminSampel');
            Route::get('/ekstraksi', 'DashboardController@ekstraksi');
            Route::get('/pcr', 'DashboardController@pcr');
            Route::get('/verifikasi', 'DashboardController@verifikasi');
            Route::get('/validasi', 'DashboardController@validasi');
            Route::group(['prefix' => 'list'], function () {
                Route::get('/sampel-perdomisili', 'DashboardController@sampelPerdomisili');
            });
        });

        Route::group(['prefix' => 'chart'], function () {
            Route::get('/regis', 'DashboardController@chartRegistrasi');
            Route::get('/sampel', 'DashboardController@chartSampel');
            Route::get('/ekstraksi', 'DashboardController@chartEkstraksi');
            Route::get('/pcr', 'DashboardController@chartPcr');
            Route::get('/positifNegatif', 'DashboardController@chartPositifNegatif');
        });
    });
});

Route::group(['prefix' => 'v1/tes-masif', 'namespace' => 'V1'], function () {
    Route::post('/bulk', 'TesMasifController@bulkTesMasif');
});
