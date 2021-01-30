<?php

const ROLE_ADMIN = 1;
const ROLE_REGISTER = 2;
const ROLE_SAMPEL = 3;
const ROLE_EKSTRAKSI = 4;
const ROLE_PCR = 5;
const ROLE_VERIFIKATOR = 6;
const ROLE_VALIDATOR = 7;
const STATUSES = [
    1 => 'Kontak Erat',
    2 => 'Suspek',
    3 => 'Probable',
    4 => 'Konfirmasi',
    5 => 'Tanpa Kriteria',
];
const HASIL_PEMERIKSAAN = ['POSITIF', 'NEGATIF', 'INKONKLUSIF', 'INVALID'];
const PROVINSI_JAWABARAT = 32;
const defaultStatus = 5; //Tanpa Kriteria

