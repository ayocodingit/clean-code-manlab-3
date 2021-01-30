<?php

function parseDate($date)
{
    if ($date) {
        return date('Y-m-d', strtotime($date));
    }
    return null;
}
function parseTime($time)
{
    $time = str_replace(':', '', $time);
    if (strlen($time) == 4) {
        return substr($time, 0, 2) . ':' . substr($time, 2, 2);
    } elseif (strlen($time) == 3) {
        return '0' . substr($time, 0, 1) . ':' . substr($time, 1, 2);
    } else {
        return '00:00';
    }
}
function parseDecimal($val)
{
    $val = str_replace(',', '.', $val);
    return floatval($val);
}
function cctor($obj)
{
    return clone $obj;
}
function getHasilDeteksiTerkecil($hasil)
{
    $result = collect(json_decode($hasil))
        ->whereNotNull('ct_value')
        ->where('target_gen', '!=', 'IC')
        ->where('ct_value', '!=', '-')
        ->sortBy('ct_value')->first();
    return $result != null && $result->ct_value > 0 ? $result->ct_value : '-';
}

function hasil_deteksi($hasil)
{
    $result = '';
    foreach ($hasil as $row) {
        $result .= $row->target_gen . ':' . $row->ct_value . ';';
    }
    return $result;
}

function usiaPasien($tanggal_lahir, $usia_tahun)
{
    if ($usia_tahun) {
        return $usia_tahun;
    }

    if ($tanggal_lahir) {
        $bday = new DateTime($tanggal_lahir);
        $today = new Datetime(date('Y-m-d'));
        $diff = $today->diff($bday);
        return $diff->y;
    }

    return '-';
}

function alamatLengkap($alamat, $provinsi, $kota, $kecamatan, $kelurahan)
{
    $alamat_lengkap = $alamat ? $alamat : '';
    $alamat_lengkap .= $kelurahan ? ' Kel. ' . $kelurahan : null;
    $alamat_lengkap .= $kecamatan ? ' Kec. ' . $kecamatan : null;
    $alamat_lengkap .= $kota ? ' ' . $kota : null;
    $alamat_lengkap .= $provinsi ? ' Prov. ' . $provinsi : null;
    return $alamat_lengkap;
}

function getCodeDagri($code)
{
    if (!$code) {
        return $code;
    }
    $code = (string)$code;
    $code = str_split($code);
    $codeDagri = '';
    foreach ($code as $key => $value) {
        $codeDagri .= $value;
        if ($key % 2 == 1 && $key < 6 && $key < count($code) - 1) {
            $codeDagri .= '.';
        }
    }
    return $codeDagri;
}

function getConvertCodeDagri($wilayah)
{
    if (!$wilayah) {
        return $wilayah;
    }
    return (int)str_replace('.', '', $wilayah);
}

function removeUnderscore($string)
{
    return str_replace('_', ' ', $string);
}
