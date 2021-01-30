<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cetak Validasi</title>

    <style type="text/css">
        @font-face {
            font-family: 'arialRegular';
            src: url("{{ public_path('fonts/arial/ARIAL_REGULAR.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'arialBold';
            src: url("{{ public_path('fonts/arial/ARIAL_BOLD.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: bold;
        }
        @font-face {
            font-family: 'arialItalic';
            src: url("{{ public_path('fonts/arial/ARIAL_ITALIC.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: italic;
        }
        body{
            font-family: "arialRegular", "arialBold", "arialItalic";
            font-size: 10pt;
        }
        #tabel-ct-scan {
            border: 1px solid black;
            border-collapse: collapse;
            text-align: center;
        }
        table#tabel-ct-scan th {
            background-color: darkseagreen;
        }
    </style>

</head>
<body marginwidth="0" marginheight="0">

    <div style="margin-bottom: 20px; margin-top: 20px;">
        <img src="{{ $kop_surat }}" width="100%" alt="" srcset="">
    </div>

    <table style="margin-top: 2%" width="100%">
        <tbody>
            <tr>
                <td width="20%">
                    <b>Nama</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                    @if ($pasien)
                        <span><b>{{$pasien['nama_lengkap']}}</b></span>
                    @endif
                </td>

                <td style="min-width:500px"></td>
                <td width=20%>
                    <b>Tanggal Registrasi</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                        {{ $tanggal_registrasi}}
                </td>
            </tr>
              <tr>
                <td width="20%">
                  <b>Umur</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                    @if ($pasien)
                        <span>{{ $umur_pasien }} Tahun</span>
                    @endif
                </td>

                <td style="min-width:500px"></td>
                <td width=20%>
                    <b>Tanggal Periksa</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                {{ $tanggal_periksa }}
                </td>

              </tr>
              <tr>

                <td width="20%">
                  <b>Jenis Kelamin</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                    @if ($pasien && $pasien['jenis_kelamin'] == 'L')
                        <span>Laki-laki</span>
                    @endif
                    @if ($pasien && $pasien['jenis_kelamin'] == 'P')
                        <span>Perempuan</span>
                    @endif
                </td>

                <td style="min-width:500px"></td>
                <td width=20%>
                    <b>Dokter Pengirim</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">

                @if ($register && $register['nama_dokter'])
                        {{ $register['nama_dokter'] }}
                    @else
                        {{ '-' }}
                    @endif
                </td>
              </tr>
              <tr>
                <td width="20%">
                  <b>Alamat</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                    @if ($pasien && $pasien['alamat_lengkap'])
                        <span>{{ $pasien['alamat_lengkap'] }}</span>
                    @endif
                    @if ($pasien && $pasien['no_rt'] && $pasien['no_rw'])
                        <span>RT/RW {{ $pasien['no_rt'] }}/{{ $pasien['no_rw'] }} </span>
                    @endif
                    @if ($pasien && $pasien->provinsi)
                        <span> {{ $pasien->provinsi->nama }} </span>
                    @endif
                    @if ($pasien && $pasien->kota)
                        <span> {{ $pasien->kota->nama }} </span>
                    @endif
                    @if ($pasien && $pasien->kecamatan)
                    <span>Kecamatan. {{ $pasien->kecamatan!=null?$pasien->kecamatan:$pasien->kecamatan->nama }} </span>
                    @endif
                    @if ($pasien && $pasien->kelurahan)
                    <span>Kelurahan. {{ $pasien->kelurahan!=null?$pasien->kelurahan:$pasien->kelurahan->nama }} </span>
                    @endif

                </td>

                <td style="min-width:500px"></td>
                <td width="20%">
                  <b>Instansi</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                @if ($register && $register['jenis_registrasi'] === 'mandiri')
                        {{ 'Labkes Provinsi Jawa Barat' }}
                    @endif

                    @if ($register && $register['jenis_registrasi'] === 'rujukan' && $register->fasyankes)
                        {{ $register->fasyankes ? $register->fasyankes['nama'] : '' }}
                    @endif

                    @if ($register && $register['jenis_registrasi'] === 'rujukan' && !$register->fasyankes)
                        {{ $register['fasyankes_pengirim'] }}
                    @endif
                </td>

              </tr>
              <tr>
                <td width="20%">

                </td>
                <td width="2%"></td>
                <td width="28%"></td>

                <td style="min-width:500px"></td>
                <td width="20%">
                  <b>Kategori</b>
                </td>
                <td width="2%">:</td>
                <td width="28%">
                @if ($register && $register['sumber_pasien'])
                        {{ $register['sumber_pasien'] }}
                    @else
                        {{ '-' }}
                    @endif
                </td>

              </tr>
        </tbody>
    </table>

    @if ($last_pemeriksaan_sampel)

        <table id="tabel-ct-scan" style="width:100%; margin-top: 2%">
            <thead>
                <tr>
                    <th width="30%"><b>Pemeriksaan</b></th>
                    <th width="30%"><b>No Sampel</b></th>
                    <th width="30%"><b>Hasil</b></th>
                    <th width="30%"><b>CT Value</b></th>
                    <th width="30%"><b>Nilai Rujukan</b></th>
                    <th width="30%"><b>Metode</b></th>
                </tr>
            </thead>
            <tbody>
                    <tr>
                        <td>SARS-CoV-2 (COVID-19)</td>
                        <td>{{$sampel['nomor_sampel']}}</td>
                        <td>
                            @if ($last_pemeriksaan_sampel['kesimpulan_pemeriksaan'])
                                <b>{{ ucfirst($last_pemeriksaan_sampel['kesimpulan_pemeriksaan'] == 'swab_ulang' ? 'swab ulang' : $last_pemeriksaan_sampel['kesimpulan_pemeriksaan']) }}</b>
                            @endif
                        </td>
                        <td>
                            @if ($last_pemeriksaan_sampel['hasil_deteksi_terkecil'] && ($last_pemeriksaan_sampel['hasil_deteksi_terkecil']['target_gen'] !== 'IC'))
                                @if ($last_pemeriksaan_sampel['hasil_deteksi_terkecil']['ct_value'] > 0)
                                    {{ round($last_pemeriksaan_sampel['hasil_deteksi_terkecil']['ct_value'], 2) }}
                                @else
                                    {{ '-' }}
                                @endif
                            @else
                                {{  '-' }}
                            @endif
                            {{-- {{ number_format((float)$last_pemeriksaan_sampel['hasil_deteksi_terkecil']['ct_value'], 2, ',','.') }} --}}
                        </td>
                        <td>
                            <span>negatif CT >= {{ $pemeriksaan_sampel['ct_normal'] ?? 40 }}</span>
                            <span>positif CT < {{ $pemeriksaan_sampel['ct_normal'] ?? 40}}</span>
                        </td>
                        <td>
                            RTq-PCR
                            {{-- -{{ $last_pemeriksaan_sampel['nama_kit_pemeriksaan'] }} --}}
                        </td>
                    </tr>
            </tbody>
        </table>
    @endif
    @if ($validator)
        <table width="97%" style="margin-top: 12%">
            <tbody>
                <tr>
                    <td width="30%"></td>
                    <td width="25%"></td>
                    <td>
                        Bandung, {{ date('d M Y') }}
                    </td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td width="25%"></td>
                    <td>PENANGGUNG JAWAB LAB COVID-19</td>
                </tr>
                <tr>
                    <td colspan="3" style="padding-top: 65px;"></td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td width="25%"></td>
                    <td>
                        <span style="text-decoration: underline">{{ $validator ? $validator->nama : ' -' }}</span>
                    </td>
                </tr>
                <tr>
                    <td width="30%"></td>
                    <td width="25%"></td>
                    <td>
                        NIP. {{ $validator ? $validator->nip : ' -' }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

</body>
</html>
