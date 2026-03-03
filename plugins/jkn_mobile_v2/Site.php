<?php

namespace Plugins\JKN_Mobile_V2;

use Systems\SiteModule;
use Systems\Lib\BpjsService;

class Site extends SiteModule
{

    public function init()
    {
        $this->consid = $this->settings->get('jkn_mobile_v2.BpjsConsID');
        $this->secretkey = $this->settings->get('jkn_mobile_v2.BpjsSecretKey');
        $this->bpjsurl = $this->settings->get('jkn_mobile_v2.BpjsAntrianUrl');
        $this->user_key = $this->settings->get('jkn_mobile_v2.BpjsUserKey');
    }

    public function routes()
    {
        $this->route('jknmobile_v2', 'getIndex');
        $this->route('jknmobile_v2/token', 'getToken');
        $this->route('jknmobile_v2/antrian/ambil', 'getAmbilAntrian');
        $this->route('jknmobile_v2/antrian/status', 'getStatusAntrian');
        $this->route('jknmobile_v2/antrian/sisa', 'getSisaAntrian');
        $this->route('jknmobile_v2/antrian/batal', 'getBatalAntrian');
        $this->route('jknmobile_v2/pasien/baru', 'getPasienBaru');
        $this->route('jknmobile_v2/pasien/checkin', 'getPasienCheckIn');
        $this->route('jknmobile_v2/operasi/rs', 'getOperasiRS');
        $this->route('jknmobile_v2/operasi/pasien', 'getOperasiPasien');
        //$this->route('jknmobile_v2/antrian/add', '_getAntreanAdd');
        //$this->route('jknmobile_v2/antrian/add/(:str)', '_getAntreanAdd');
        $this->route('jknmobile_v2/antrian/updatewaktu', '_getAntreanUpdateWaktu');
        $this->route('jknmobile_v2/antrian/updatewaktu/(:str)', '_getAntreanUpdateWaktu');
      
      	$this->route('jknmobile_v2/antrian/updatewaktu2', '_getAntreanUpdateWaktu2');
        $this->route('jknmobile_v2/antrian/updatewaktu2/(:str)', '_getAntreanUpdateWaktu2');
        $this->route('jknmobile_v2/antrian/updatewaktu2/(:str)/(:str)', '_getAntreanUpdateWaktu2');
      	$this->route('jknmobile_v2/antrian/updatewaktu2mjkn', '_getAntreanUpdateWaktu2MJKN');
        $this->route('jknmobile_v2/antrian/updatewaktu2mjkn/(:str)', '_getAntreanUpdateWaktu2MJKN');
        $this->route('jknmobile_v2/antrian/updatewaktu2mjkn/(:str)/(:str)', '_getAntreanUpdateWaktu2MJKN');
      
        $this->route('jknmobile_v2/antrian/waktutunggu/(:str)/(:str)/(:str)', '_getAntreanWaktuTunggu');
        $this->route('jknmobile_v2/antrian/tanggaltunggu/(:str)/(:str)', '_getAntreanWaktuTungguTanggal');
        $this->route('jknmobile_v2/antrian/listtask/(:str)', '_getAntreanGetListTask');
        $this->route('jknmobile_v2/jadwal/(:str)/(:str)', '_getJadwal');
      	$this->route('jknmobile_v2/coba','getCobaAdd');
        $this->route('jknmobile_v2/anjungan/add','_postMjknAdd');
        $this->route('jknmobile_v2/aplicare/(:str)', 'getAplicare');
      	$this->route('jknmobile_v2/anjungan/jadwal', '_getJadwalSimrs');
      	$this->route('jknmobile_v2/batalmanual/(:str)', 'postBatalManual');
      	$this->route('jknmobile_v2/antrian/pertanggal/(:str)', 'getListAntrol');
      	$this->route('jknmobile_v2/antrian/addmanual/(:str)/(:str)', '_postMjknManual');
    }

    public function getIndex()
    {
        $referensi_poli = $this->db('maping_poli_bpjs')->toArray();
        echo $this->draw('index.html', ['referensi_poli' => $referensi_poli]);
        exit();
    }

    public function getToken()
    {
        echo $this->_resultToken();
        exit();
    }

    private function _resultToken()
    {
        header("Content-Type: application/json");
        $konten = trim(file_get_contents("php://input"));
        $header = apache_request_headers();
        $response = array();
        if ($header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username') && $header[$this->settings->get('jkn_mobile_v2.header_password')] == $this->settings->get('jkn_mobile_v2.x_password')) {
            $response = array(
                'response' => array(
                    'token' => $this->_getToken()
                ),
                'metadata' => array(
                    'message' => 'Ok',
                    'code' => 200
                )
            );
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
        }
        echo json_encode($response);
    }

    public function getAmbilAntrian()
    {
        echo $this->_resultAmbilAntrian();
        exit();
    }

    private function _resultAmbilAntrian()
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;

        date_default_timezone_set($this->settings->get('settings.timezone'));

        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {

            $tanggal=$decode['tanggalperiksa'];
            $tentukan_hari=date('D',strtotime($tanggal));
            $day = array(
              'Sun' => 'AKHAD',
              'Mon' => 'SENIN',
              'Tue' => 'SELASA',
              'Wed' => 'RABU',
              'Thu' => 'KAMIS',
              'Fri' => 'JUMAT',
              'Sat' => 'SABTU'
            );
            $hari=$day[$tentukan_hari];
            $cek_rujukan = $this->db('bridging_sep')->where('no_rujukan', $decode['nomorreferensi'])->group('tglrujukan')->oneArray();

            $h1 = strtotime('+1 days' , strtotime(date('Y-m-d'))) ;
            $h1 = date('Y-m-d', $h1);
            $_h1 = date('d-m-Y', strtotime($h1));
            if($cek_rujukan > 0) {
              $h7 = strtotime('+90 days', strtotime($cek_rujukan['tglrujukan']));
            } else {
              $h7 = strtotime('+7 days', strtotime(date('Y-m-d'))) ;
            }
            $h7 = date('Y-m-d', $h7);
            $_h7 = date('d-m-Y', strtotime($h7));

            $data_pasien = $this->db('pasien')->where('no_peserta', $decode['nomorkartu'])->oneArray();
            $poli = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $decode['kodepoli'])->oneArray();
            $dokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $decode['kodedokter'])->oneArray();
            $cek_kouta = $this->db()->pdo()->prepare("SELECT jadwal.kuota - (SELECT COUNT(booking_registrasi.tanggal_periksa) FROM booking_registrasi WHERE booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' AND booking_registrasi.kd_dokter=jadwal.kd_dokter) as sisa_kouta, jadwal.kd_dokter, jadwal.kd_poli, jadwal.jam_mulai as jam_mulai, poliklinik.nm_poli, dokter.nm_dokter, jadwal.kuota FROM jadwal INNER JOIN maping_poli_bpjs ON maping_poli_bpjs.kd_poli_rs=jadwal.kd_poli INNER JOIN poliklinik ON poliklinik.kd_poli=jadwal.kd_poli INNER JOIN dokter ON dokter.kd_dokter=jadwal.kd_dokter WHERE jadwal.hari_kerja='$hari' AND maping_poli_bpjs.kd_poli_bpjs='$decode[kodepoli]' GROUP BY jadwal.kd_dokter HAVING sisa_kouta > 0 ORDER BY sisa_kouta DESC LIMIT 1");
            $cek_kouta->execute();
            $cek_kouta = $cek_kouta->fetch();
            $jadwal = $this->db('jadwal')
                ->join('maping_dokter_dpjpvclaim', 'maping_dokter_dpjpvclaim.kd_dokter=jadwal.kd_dokter')
                ->where('maping_dokter_dpjpvclaim.kd_dokter_bpjs', $decode['kodedokter'])
                ->where('hari_kerja', $hari)
                ->where('jam_mulai', strtok($decode['jampraktek'], '-').':00')
                ->where('jam_selesai', substr($decode['jampraktek'], strpos($decode['jampraktek'], "-") + 1).':00')
                ->oneArray();

            $cek_referensi = $this->db('mlite_antrian_referensi')->where('nomor_referensi', $decode['nomorreferensi'])->where('tanggal_periksa', $decode['tanggalperiksa'])->oneArray();
            $cek_referensi_noka = $this->db('mlite_antrian_referensi')->where('nomor_kartu', $decode['nomorkartu'])->where('tanggal_periksa', $decode['tanggalperiksa'])->oneArray();

            if($cek_referensi > 0) {
               $errors[] = 'Anda sudah terdaftar dalam antrian menggunakan nomor rujukan yang sama ditanggal '.$decode['tanggalperiksa'];
            }
            if($cek_referensi_noka > 0) {
               $errors[] = 'Anda sudah terdaftar dalam antrian ditanggal '.$cek_referensi_noka['tanggal_periksa'].'. Silahkan pilih tanggal lain.';
            }
            if(empty($decode['nomorkartu'])) {
               $errors[] = 'Nomor kartu tidak boleh kosong';
            }
            if (!empty($decode['nomorkartu']) && mb_strlen($decode['nomorkartu'], 'UTF-8') < 13){
               $errors[] = 'Nomor kartu harus 13 digit';
            }
            if (!empty($decode['nomorkartu']) && !ctype_digit($decode['nomorkartu']) ){
               $errors[] = 'Nomor kartu harus mengandung angka saja!!';
            }
            if(empty($decode['nik'])) {
               $errors[] = 'Nomor kartu tidak boleh kosong';
            }
            if(!empty($decode['nik']) && mb_strlen($decode['nik'], 'UTF-8') < 16){
               $errors[] = 'Nomor KTP harus 16 digiti atau format tidak sesuai';
            }
            if (!empty($decode['nik']) && !ctype_digit($decode['nik']) ){
               $errors[] = 'Nomor kartu harus mengandung angka saja!!';
            }
            if(empty($decode['tanggalperiksa'])) {
               $errors[] = 'Anda belum memilih tanggal periksa';
            }
            if(!empty($decode['tanggalperiksa']) && $decode['tanggalperiksa'] < $h1 || $decode['tanggalperiksa'] > $h7) {
               $errors[] = 'Tanggal periksa bisa dilakukan tanggal '.$_h1.' hingga tanggal '.$_h7;
            }
            if (!empty($decode['tanggalperiksa']) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-k9]|[1-2][0-9]|3[0-1])$/",$decode['tanggalperiksa'])) {
               $errors[] = 'Format tanggal periksa tidak sesuai';
            }
            if(!empty($decode['tanggalperiksa']) && $decode['tanggalperiksa'] == $cek_referensi['tanggal_periksa']) {
               $errors[] = 'Anda sudah terdaftar dalam antrian ditanggal '.$decode['tanggalperiksa'];
            }
            if(empty($decode['kodepoli'])) {
               $errors[] = 'Kode poli tidak boleh kosong';
            }
            if(!empty($decode['kodepoli']) && $poli == 0) {
               $errors[] = 'Kode poli tidak ditemukan';
            }
            if(empty($decode['kodedokter'])) {
               $errors[] = 'Kode dokter tidak boleh kosong';
            }
            if(!empty($decode['kodedokter']) && $dokter == 0) {
               $errors[] = 'Kode dokter tidak ditemukan';
            }
            if(!empty($decode['jeniskunjungan']) && $decode['jeniskunjungan'] < 1 || $decode['jeniskunjungan'] > 4) {
               $errors[] = 'Jenis kunjungan tidak ditemukan';
            }

            if(!empty($errors)) {
                foreach($errors as $error) {
                    $response = array(
                        'metadata' => array(
                            'message' => $this->_getErrors($error),
                            'code' => 201
                        )
                    );
                };
                http_response_code(201);
            } else {

                if ($cek_kouta['sisa_kouta'] > 0) {
                    if(!$data_pasien) {
                        $response = array(
                            'metadata' => array(
                                'message' =>  'Data pasien ini tidak ditemukan',
                                'code' => 202
                            )
                        );
                        http_response_code(202);
                    } else {
                        // Get antrian poli
                        $no_reg = $this->core->setNoBooking($dokter['kd_dokter'], $decode['tanggalperiksa']);
                        $minutes = $no_reg * 10;
                        $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($cek_kouta['jam_mulai'])));
                        $keterangan = 'Peserta harap datang 30 menit lebih awal.';
                        $query = $this->db('booking_registrasi')->save([
                            'tanggal_booking' => date('Y-m-d'),
                            'jam_booking' => date('H:i:s'),
                            'no_rkm_medis' => $data_pasien['no_rkm_medis'],
                            'tanggal_periksa' => $decode['tanggalperiksa'],
                            'kd_dokter' => $cek_kouta['kd_dokter'],
                            'kd_poli' => $cek_kouta['kd_poli'],
                            'no_reg' => $no_reg,
                            'kd_pj' => 'BPJ',
                            'limit_reg' => 1,
                            'waktu_kunjungan' => $decode['tanggalperiksa'].' '.$cek_kouta['jam_mulai'],
                            'status' => 'Belum'
                        ]);
                        if ($query) {
                            $kodebooking = date('Ymdhis').''.$decode['kodepoli'].''.$no_reg;
                            $response = array(
                                'response' => array(
                                    'nomorantrean' => $decode['kodepoli'].'-'.$no_reg,
                                    'angkaantrean' => $no_reg,
                                    'kodebooking' => $kodebooking,
                                    'pasienbaru'=>0,
                                    'norm' => $data_pasien['no_rkm_medis'],
                                    'namapoli' => $cek_kouta['nm_poli'],
                                    'namadokter' => $cek_kouta['nm_dokter'],
                                    'estimasidilayani' => strtotime($decode['tanggalperiksa'].' '.$cek_kouta['jam_mulai']) * 1000,
                                    'sisakuotajkn' => ($cek_kouta['sisa_kouta']-1),
                                    'kuotajkn' => intval($cek_kouta['kuota']),
                                    'sisakuotanonjkn' => ($cek_kouta['sisa_kouta']-1),
                                    'kuotanonjkn' => intval($cek_kouta['kuota']),
                                    'keterangan' => $keterangan
                                ),
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );

                            if(!empty($decode['nomorreferensi'])) {
                                $this->db('mlite_antrian_referensi')->save([
                                    'tanggal_periksa' => $decode['tanggalperiksa'],
                                    'no_rkm_medis' => $data_pasien['no_rkm_medis'],
                                    'nomor_kartu' => $decode['nomorkartu'],
                                    'nomor_referensi' => $decode['nomorreferensi'],
                                    'kodebooking' => $kodebooking,
                                    'jenis_kunjungan' => $decode['jeniskunjungan'],
                                    'status_kirim' => 'Sudah'
                                ]);
                            }
                            http_response_code(200);
                            /*
                            $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $cek_kouta['kd_dokter'])->oneArray();
                            $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $cek_kouta['kd_poli'])->oneArray();

                            $data = [
                                'kodebooking' => $decode['nomorreferensi'],
                                'jenispasien' => 'JKN',
                                'nomorkartu' => $decode['nomorkartu'],
                                'nik' => $decode['nik'],
                                'nohp' => $data_pasien['no_tlp'],
                                'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
                                'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
                                'pasienbaru' => 0,
                                'norm' => $data_pasien['no_rkm_medis'],
                                'tanggalperiksa' => $decode['tanggalperiksa'],
                                'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
                                'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
                                'jampraktek' => $jadwal['jam_mulai'].'-'.$jadwal['jam_selesai'],
                                'jeniskunjungan' => $decode['jeniskunjungan'],
                                'nomorreferensi' => $decode['nomorreferensi'],
                                'nomorantrean' => $decode['kodepoli'].'-'.$no_reg,
                                'angkaantrean' => $no_reg,
                                'estimasidilayani' => strtotime($decode['tanggalperiksa'].' '.$cek_kouta['jam_mulai']) * 1000,
                                'sisakuotajkn' => ($cek_kouta['sisa_kouta']-1),
                                'kuotajkn' => $cek_kouta['kuota'],
                                'sisakuotanonjkn' => ($cek_kouta['sisa_kouta']-1),
                                'kuotanonjkn' => $cek_kouta['kuota'],
                                'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
                            ];
                            $data = json_encode($data);
                            $url = $this->bpjsurl.'antrean/add';
                            $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                            $data = json_decode($output, true);
                            if($data['metadata']['code'] == 200){
                              if(!empty($decode['nomorreferensi'])) {
                                $this->db('mlite_antrian_referensi')->save([
                                    'tanggal_periksa' => $decode['tanggalperiksa'],
                                    'nomor_kartu' => $decode['nomorkartu'],
                                    'nomor_referensi' => $decode['nomorreferensi'],
                                    'status_kirim' => 'Sudah'
                                ]);
                              }
                            }
                            */
                        } else {
                            $response = array(
                                'metadata' => array(
                                    'message' => "Maaf Terjadi Kesalahan, Hubungi layanan pelanggang Rumah Sakit..",
                                    'code' => 201
                                )
                            );
                        }
                    }
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => "Jadwal tidak tersedia atau kuota penuh! Silahkan pilih tanggal lain!",
                            'code' => 201
                        )
                    );
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getStatusAntrian()
    {
        echo $this->_resultStatusAntrian();
        exit();
    }

    private function _resultStatusAntrian()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();

        $tentukan_hari=date('D',strtotime($decode['tanggalperiksa']));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];

        $kdpoli = $this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $decode['kodepoli'])->oneArray();
        $kddokter = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $decode['kodedokter'])->oneArray();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            if(!$this->db('maping_poli_bpjs')->where('kd_poli_bpjs', $decode['kodepoli'])->oneArray()){
                $response = array(
                    'metadata' => array(
                        'message' => 'Poli Tidak Ditemukan',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!$this->db('maping_dokter_dpjpvclaim')->where('kd_dokter_bpjs', $decode['kodedokter'])->oneArray()){
                $response = array(
                    'metadata' => array(
                        'message' => 'Dokter Tidak Ditemukan',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$decode['tanggalperiksa'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Tanggal Tidak Sesuai, format yang benar adalah yyyy-mm-dd',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!$this->db('jadwal')
                ->join('maping_dokter_dpjpvclaim', 'maping_dokter_dpjpvclaim.kd_dokter=jadwal.kd_dokter')
                ->where('maping_dokter_dpjpvclaim.kd_dokter_bpjs', $decode['kodedokter'])
                ->where('hari_kerja', $hari)
                ->where('jam_mulai', strtok($decode['jampraktek'], '-').':00')
                ->where('jam_selesai', substr($decode['jampraktek'], strpos($decode['jampraktek'], "-") + 1).':00')
                ->oneArray()){
                $response = array(
                    'metadata' => array(
                        'message' => 'Jadwal Praktek Tidak Ditemukan Pada Hari '.$hari.' Tanggal '.$decode['tanggalperiksa'],
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(date("Y-m-d")>$decode['tanggalperiksa']){
                $response = array(
                    'metadata' => array(
                        'message' => 'Tanggal Periksa Tidak Berlaku',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else{
                $jammulai   = substr($decode['jampraktek'],0,5);
                $jamselesai = substr($decode['jampraktek'],6,5);

                $kuota = $this->db()->pdo()->prepare("SELECT jadwal.kuota - (SELECT COUNT(booking_registrasi.tanggal_periksa) FROM booking_registrasi WHERE booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' AND booking_registrasi.kd_dokter=jadwal.kd_dokter) as sisa_kouta, jadwal.kd_dokter, jadwal.kd_poli, jadwal.jam_mulai as jam_mulai, poliklinik.nm_poli, dokter.nm_dokter, jadwal.kuota FROM jadwal INNER JOIN maping_poli_bpjs ON maping_poli_bpjs.kd_poli_rs=jadwal.kd_poli INNER JOIN poliklinik ON poliklinik.kd_poli=jadwal.kd_poli INNER JOIN dokter ON dokter.kd_dokter=jadwal.kd_dokter WHERE jadwal.hari_kerja='$hari' AND maping_poli_bpjs.kd_poli_bpjs='$decode[kodepoli]' GROUP BY jadwal.kd_dokter HAVING sisa_kouta > 0 ORDER BY sisa_kouta DESC LIMIT 1");
                $kuota->execute();
                $kuota = $kuota->fetch();

                $max_antrian = $this->db()->pdo()->prepare("SELECT booking_registrasi.no_reg FROM booking_registrasi WHERE booking_registrasi.status='Belum' AND booking_registrasi.kd_dokter='$kddokter[kd_dokter]' AND booking_registrasi.kd_poli='$kdpoli[kd_poli_rs]' AND booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' ORDER BY CONVERT(RIGHT(booking_registrasi.no_reg,3),signed) LIMIT 1 ");

                if($decode['tanggalperiksa'] == date('Y-m-d')) {
                  $max_antrian = $this->db()->pdo()->prepare("SELECT reg_periksa.no_reg FROM reg_periksa WHERE reg_periksa.stts='Belum' AND reg_periksa.kd_dokter='$kddokter[kd_dokter]' AND reg_periksa.kd_poli='$kdpoli[kd_poli_rs]' AND reg_periksa.tgl_registrasi='$decode[tanggalperiksa]' ORDER BY CONVERT(RIGHT(reg_periksa.no_reg,3),signed) LIMIT 1 ");
                }
                $max_antrian->execute();
                $max_antrian = $max_antrian->fetch();

                $data = $this->db()->pdo()->prepare("SELECT poliklinik.nm_poli,COUNT(booking_registrasi.kd_poli) as total_antrean,dokter.nm_dokter,
                    IFNULL(SUM(CASE WHEN booking_registrasi.status ='Belum' THEN 1 ELSE 0 END),0) as sisa_antrean,
                    ('Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke bagian layanan pelanggan, Terima Kasih ..') as keterangan
                    FROM booking_registrasi INNER JOIN poliklinik ON poliklinik.kd_poli=booking_registrasi.kd_poli INNER JOIN dokter ON booking_registrasi.kd_dokter=dokter.kd_dokter
                    WHERE booking_registrasi.tanggal_periksa='$decode[tanggalperiksa]' AND booking_registrasi.kd_poli='$kdpoli[kd_poli_rs]' and booking_registrasi.kd_dokter='$kddokter[kd_dokter]'");
                if($decode['tanggalperiksa'] == date('Y-m-d')) {
                  $data = $this->db()->pdo()->prepare("SELECT poliklinik.nm_poli,COUNT(reg_periksa.kd_poli) as total_antrean,dokter.nm_dokter,
                      IFNULL(SUM(CASE WHEN reg_periksa.stts ='Belum' THEN 1 ELSE 0 END),0) as sisa_antrean,
                      ('Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke layanan pelanggan, Terima Kasih ..') as keterangan
                      FROM reg_periksa INNER JOIN poliklinik ON poliklinik.kd_poli=reg_periksa.kd_poli INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter
                      WHERE reg_periksa.tgl_registrasi='$decode[tanggalperiksa]' AND reg_periksa.kd_poli='$kdpoli[kd_poli_rs]' and reg_periksa.kd_dokter='$kddokter[kd_dokter]'
                      and jam_reg between '$jammulai:00' and '$jamselesai:00'");
                }
                $data->execute();
                $data = $data->fetch();

                if ($data['sisa_antrean'] >0) {
                    $response = array(
                        'response' => array(
                            'namapoli' => $data['nm_poli'],
                            'namadokter' => $data['nm_dokter'],
                            'totalantrean' => $data['total_antrean'],
                            'sisaantrean' => ($data['sisa_antrean']>0?$data['sisa_antrean']:0),
                            'antreanpanggil' => "A-".$max_antrian['no_reg'],
                            'sisakuotajkn' => ($kuota['kuota']-$data['total_antrean']),
                            'kuotajkn' => intval($kuota['kuota']),
                            'sisakuotanonjkn' => ($kuota['kuota']-$data['total_antrean']),
                            'kuotanonjkn' => intval($kuota['kuota']),
                            'keterangan' => $data['keterangan']
                        ),
                        'metadata' => array(
                            'message' => 'Ok',
                            'code' => 200
                        )
                    );
                    http_response_code(200);
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Maaf belum ada antrian ditanggal ' . $decode['tanggalperiksa'],
                             'code' => 201
                        )
                    );
                    http_response_code(201);
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
        }
        echo json_encode($response);
    }

    public function getSisaAntrian()
    {
        echo $this->_resultSisaAntrian();
        exit();
    }

    private function _resultSisaAntrian()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            if(empty($decode['kodebooking'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Booking tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodebooking'],"'")||strpos($decode['kodebooking'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Booking salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else{
                $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                $booking_registrasi = [];
                $pasien = [];
                if($referensi) {
                  $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                  $booking_registrasi = $this->db('booking_registrasi')
                    ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                    ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                    ->oneArray();
                }
                if(!$booking_registrasi) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data Booking tidak ditemukan',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }else{
                    if($booking_registrasi['status']=='Belum'){
                        $response = array(
                            'metadata' => array(
                                'message' => 'Anda belum melakukan checkin, Silahkan checkin terlebih dahulu',
                                'code' => 201
                            )
                        );
                        http_response_code(201);
                    }else if($booking_registrasi['status']=='Batal'){
                        $response = array(
                            'metadata' => array(
                                'message' => 'Data Booking Sudah Dibatalkan',
                                'code' => 201
                            )
                        );
                        http_response_code(201);
                    }else if($booking_registrasi['status']=='Terdaftar'){
                        $noreg = $this->db('reg_periksa')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tgl_registrasi', $booking_registrasi['tanggal_periksa'])->oneArray();

                        $max_antrian = $this->db()->pdo()->prepare("SELECT reg_periksa.no_reg FROM reg_periksa WHERE reg_periksa.stts='Belum' AND reg_periksa.kd_dokter='$booking_registrasi[kd_dokter]' AND reg_periksa.kd_poli='$booking_registrasi[kd_poli]' AND reg_periksa.tgl_registrasi='$booking_registrasi[tanggal_periksa]' ORDER BY CONVERT(RIGHT(reg_periksa.no_reg,3),signed) LIMIT 1 ");
                        $max_antrian->execute();
                        $max_antrian = $max_antrian->fetch();

                        $data = $this->db()->pdo()->prepare("SELECT reg_periksa.kd_poli,poliklinik.nm_poli,dokter.nm_dokter,
                            reg_periksa.no_reg,COUNT(reg_periksa.no_rawat) as total_antrean,
                            IFNULL(SUM(CASE WHEN reg_periksa.stts ='Belum' THEN 1 ELSE 0 END),0) as sisa_antrean
                            FROM reg_periksa INNER JOIN poliklinik ON poliklinik.kd_poli=reg_periksa.kd_poli
                            INNER JOIN dokter ON dokter.kd_dokter=reg_periksa.kd_dokter
                            WHERE reg_periksa.kd_dokter='$booking_registrasi[kd_dokter]' and reg_periksa.kd_poli='$booking_registrasi[kd_poli]'and reg_periksa.tgl_registrasi='$booking_registrasi[tanggal_periksa]'");
                        $data->execute();
                        $data = $data->fetch();

                        if ($data['nm_poli'] != '') {
                            $response = array(
                                'response' => array(
                                    'nomorantrean' => "A-".$noreg['no_reg'],
                                    'namapoli' => $data['nm_poli'],
                                    'namadokter' => $data['nm_dokter'],
                                    'sisaantrean' => ($data['sisa_antrean']>0?$data['sisa_antrean']:0),
                                    'antreanpanggil' => "A-".$max_antrian['no_reg'],
                                    'waktutunggu' => (($data['sisa_antrean']*10)*1000),
                                    'keterangan' => "Datanglah Minimal 30 Menit, jika no antrian anda terlewat, silakan konfirmasi ke layanan pelanggan, Terima Kasih .."
                                ),
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );
                            http_response_code(200);
                        } else {
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Antrean Tidak Ditemukan !',
                                    'code' => 201
                                )
                            );
                            http_response_code(201);
                        }
                    }
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getBatalAntrian()
    {
        echo $this->_resultBatalAntrian();
        exit();
    }

    private function _resultBatalAntrian()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            if(empty($decode['kodebooking'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Booking tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodebooking'],"'")||strpos($decode['kodebooking'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Booking salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['keterangan'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Keterangan tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['keterangan'],"'")||strpos($decode['keterangan'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Keterangan salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else{
                $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                $booking_registrasi = [];
                $pasien = [];
                if($referensi) {
                  $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                  $booking_registrasi = $this->db('booking_registrasi')
                    ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                    ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                    ->oneArray();
                }
                if(!$booking_registrasi) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data Booking tidak ditemukan',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }else{
                    if(date("Y-m-d")>$booking_registrasi['tanggal_periksa']){
                        $response = array(
                            'metadata' => array(
                                'message' => 'Pembatalan Antrean tidak berlaku mundur',
                                'code' => 201
                            )
                        );
                        http_response_code(201);
                    }else if($booking_registrasi['status']=='Terdaftar'){
                        $response = array(
                            'metadata' => array(
                                'message' => 'Anda Sudah Checkin, Pendaftaran Tidak Bisa Dibatalkan',
                                'code' => 201
                            )
                        );
                        http_response_code(201);
                    }else if($booking_registrasi['status']=='Belum'){
                        $batal = $this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->delete();
                        if($batal){
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Ok',
                                    'code' => 200
                                )
                            );
                            $this->db('mlite_antrian_referensi_batal')->save([
                                'tanggal_batal' => date('Y-m-d'),
                                'nomor_referensi' => $decode['kodebooking'],
                                'keterangan' => $decode['keterangan']
                            ]);
                            $this->db('mlite_antrian_referensi')->where('nomor_referensi', $decode['kodebooking'])->delete();
                            http_response_code(200);
                        }else{
                            $response = array(
                                'metadata' => array(
                                    'message' => "Maaf Terjadi Kesalahan, Hubungi Admnistrator..",
                                    'code' => 201
                                )
                            );
                            http_response_code(201);
                        }
                    }
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getPasienBaru()
    {
        echo $this->_resultPasienBaru();
        exit();
    }

    private function _resultPasienBaru()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            if (empty($decode['nomorkartu'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Nomor Kartu tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if (mb_strlen($decode['nomorkartu'], 'UTF-8') <> 13){
                $response = array(
                    'metadata' => array(
                        'message' => 'Nomor Kartu harus 13 digit',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if (!preg_match("/^[0-9]{13}$/",$decode['nomorkartu'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nomor Kartu tidak sesuai',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }elseif (empty($decode['nik'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'NIK tidak boleh kosong ',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }elseif (strlen($decode['nik']) <> 16) {
                $response = array(
                    'metadata' => array(
                        'message' => 'NIK harus 16 digit ',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if (!preg_match("/^[0-9]{16}$/",$decode['nik'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format NIK tidak sesuai',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }elseif (empty($decode['nomorkk'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nomor KK tidak boleh kosong ',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }elseif (strlen($decode['nomorkk']) <> 16) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nomor KK harus 16 digit ',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if (!preg_match("/^[0-9]{16}$/",$decode['nomorkk'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nomor KK tidak sesuai',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['nama'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nama tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['nama'],"'")||strpos($decode['nama'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nama salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['jeniskelamin'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Jenis Kelamin tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['jeniskelamin'],"'")||strpos($decode['jeniskelamin'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Jenis Kelamin tidak ditemukan',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!(($decode['jeniskelamin']=="L")||($decode['jeniskelamin']=="P"))){
                $response = array(
                    'metadata' => array(
                        'message' => 'Jenis Kelmain tidak ditemukan',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['tanggallahir'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Tanggal Lahir tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$decode['tanggallahir'])){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Tanggal Lahir tidak sesuai, format yang benar adalah yyyy-mm-dd',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['nohp'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'No.HP tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['nohp'],"'")||strpos($decode['nohp'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format No.HP salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['alamat'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Alamat tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['alamat'],"'")||strpos($decode['alamat'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Alamat salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['kodeprop'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Propinsi tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodeprop'],"'")||strpos($decode['kodeprop'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Propinsi salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['namaprop'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nama Propinsi tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['namaprop'],"'")||strpos($decode['namaprop'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nama Propinsi salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['kodedati2'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Dati II tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodedati2'],"'")||strpos($decode['kodedati2'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Dati II salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['namadati2'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nama Dati II tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['namadati2'],"'")||strpos($decode['namadati2'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nama Dati II salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['kodekec'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Kecamatan tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodekec'],"'")||strpos($decode['kodekec'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Kecamatan salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['namakec'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nama Kecamatan tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['namakec'],"'")||strpos($decode['namakec'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nama Kecamatan salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['kodekel'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Kelurahan tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodekel'],"'")||strpos($decode['kodekel'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Kelurahan salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['namakel'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Nama Kelurahan tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['namakel'],"'")||strpos($decode['namakel'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Nama Kelurahan salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['rw'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'RW tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['rw'],"'")||strpos($decode['rw'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format RW salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(empty($decode['rt'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'RT tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['rt'],"'")||strpos($decode['rt'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format RT salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else{
                if($this->db('pasien')->where('no_peserta', $decode['nomorkartu'])->oneArray()) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data pasien ini sudah terdaftar',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                } else {
                    $date = date('Y-m-d');

                    $_POST['no_rkm_medis'] = $this->core->setNoRM();
                    $_POST['nm_pasien'] = $decode['nama'];
                    $_POST['no_ktp'] = $decode['nik'];
                    $_POST['jk'] = $decode['jeniskelamin'];
                    $_POST['tmp_lahir'] = '-';
                    $_POST['tgl_lahir'] = $decode['tanggallahir'];
                    $_POST['nm_ibu'] = '-';
                    $_POST['alamat'] = $decode['alamat'];
                    $_POST['gol_darah'] = '-';
                    $_POST['pekerjaan'] = '-';
                    $_POST['stts_nikah'] = 'JOMBLO';
                    $_POST['agama'] = '-';
                    $_POST['tgl_daftar'] = $date;
                    $_POST['no_tlp'] = $decode['nohp'];
                    $_POST['umur'] = $this->_setUmur($decode['tanggallahir']);;
                    $_POST['pnd'] = '-';
                    $_POST['keluarga'] = 'AYAH';
                    $_POST['namakeluarga'] = '-';
                    $_POST['kd_pj'] = 'BPJ';
                    $_POST['no_peserta'] = $decode['nomorkartu'];
                    $_POST['kd_kel'] = $this->settings->get('jkn_mobile_v2.kdkel');
                    $_POST['kd_kec'] = $this->settings->get('jkn_mobile_v2.kdkec');
                    $_POST['kd_kab'] = $this->settings->get('jkn_mobile_v2.kdkab');
                    $_POST['pekerjaanpj'] = '-';
                    $_POST['alamatpj'] = '-';
                    $_POST['kelurahanpj'] = '-';
                    $_POST['kecamatanpj'] = '-';
                    $_POST['kabupatenpj'] = '-';
                    $_POST['perusahaan_pasien'] = $this->settings->get('jkn_mobile_v2.perusahaan_pasien');
                    $_POST['suku_bangsa'] = $this->settings->get('jkn_mobile_v2.suku_bangsa');
                    $_POST['bahasa_pasien'] = $this->settings->get('jkn_mobile_v2.bahasa_pasien');
                    $_POST['cacat_fisik'] = $this->settings->get('jkn_mobile_v2.cacat_fisik');
                    $_POST['email'] = '';
                    $_POST['nip'] = '';
                    $_POST['kd_prop'] = $this->settings->get('jkn_mobile_v2.kdprop');
                    $_POST['propinsipj'] = '-';

                    $query = $this->db('pasien')->save($_POST);

                    if($query) {
                        $this->core->db()->pdo()->exec("UPDATE set_no_rkm_medis SET no_rkm_medis='$_POST[no_rkm_medis]'");
                    }

                    $pasien = $this->db('pasien')->where('no_peserta', $decode['nomorkartu'])->oneArray();
                    $response = array(
                        'response' => array(
                            'norm' => $_POST['no_rkm_medis']
                        ),
                        'metadata' => array(
                            'message' => 'Pasien berhasil mendapatkann nomor RM, silahkan lanjutkan ke booking. Pasien tidak perlu ke admisi.',
                            'code' => 200
                        )
                    );
                    http_response_code(200);
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getPasienCheckIn()
    {
        echo $this->_resultPasienCheckIn();
        exit();
    }

    private function _resultPasienCheckIn()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            $tanggal=date("Y-m-d", ($decode['waktu']/1000));
            if(empty($decode['kodebooking'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Kode Booking tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(strpos($decode['kodebooking'],"'")||strpos($decode['kodebooking'],"\\")){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Kode Booking salah',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }elseif(empty($decode['waktu'])) {
                $response = array(
                    'metadata' => array(
                        'message' => 'Waktu tidak boleh kosong',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$tanggal)){
                $response = array(
                    'metadata' => array(
                        'message' => 'Format Tanggal Checkin tidak sesuai, format yang benar adalah yyyy-mm-dd',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else if(date("Y-m-d")>$tanggal){
                $response = array(
                    'metadata' => array(
                        'message' => 'Waktu Checkin tidak berlaku mundur',
                        'code' => 201
                    )
                );
                http_response_code(201);
            }else{
                $referensi = $this->db('mlite_antrian_referensi')->where('kodebooking', $decode['kodebooking'])->oneArray();
                $booking_registrasi = [];
                $pasien = [];
                if($referensi) {
                  $pasien = $this->db('pasien')->where('no_peserta', $referensi['nomor_kartu'])->oneArray();
                  $booking_registrasi = $this->db('booking_registrasi')
                    ->where('no_rkm_medis', $pasien['no_rkm_medis'])
                    ->where('tanggal_periksa', $referensi['tanggal_periksa'])
                    ->oneArray();
                }
                if(!$booking_registrasi) {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Data Booking tidak ditemukan',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }else{
                    if($booking_registrasi['status']=='Terdaftar'){
                        $response = array(
                            'metadata' => array(
                                'message' => 'Anda Sudah Checkin',
                                'code' => 201
                            )
                        );
                        http_response_code(201);
                    }else if($booking_registrasi['status']=='Belum'){
                        $interval = $this->db()->pdo()->prepare("SELECT (TO_DAYS('$booking_registrasi[tanggal_periksa]')-TO_DAYS('$tanggal'))");
                        $interval->execute();
                        $interval = $interval->fetch();

                        if($interval[0]<=0){
                            $response = array(
                                'metadata' => array(
                                    'message' => 'Chekin Anda sudah expired, maksimal 1 hari sebelum tanggal periksa. Silahkan konfirmasi ke layanan pelanggan',
                                    'code' => 201
                                )
                            );
                            http_response_code(201);
                        }else{
                            $cek_stts_daftar = $this->db('reg_periksa')->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])->count();
                            $_POST['stts_daftar'] = 'Baru';
                            if($cek_stts_daftar > 0) {
                              $_POST['stts_daftar'] = 'Lama';
                            }

                            $biaya_reg = $this->db('poliklinik')->where('kd_poli', $booking_registrasi['kd_poli'])->oneArray();
                            $_POST['biaya_reg'] = $biaya_reg['registrasi'];
                            if($_POST['stts_daftar'] == 'Lama') {
                              $_POST['biaya_reg'] = $biaya_reg['registrasilama'];
                            }

                            $cek_status_poli = $this->db('reg_periksa')->where('no_rkm_medis', $booking_registrasi['no_rkm_medis'])->where('kd_poli', $booking_registrasi['kd_poli'])->count();
                            $_POST['status_poli'] = 'Baru';
                            if($cek_status_poli > 0) {
                              $_POST['status_poli'] = 'Lama';
                            }

                            // set umur
                            $tanggal = new \DateTime($this->core->getPasienInfo('tgl_lahir', $booking_registrasi['no_rkm_medis']));
                            $today = new \DateTime(date('Y-m-d'));
                            $y = $today->diff($tanggal)->y;
                            $m = $today->diff($tanggal)->m;
                            $d = $today->diff($tanggal)->d;

                            $umur="0";
                            $sttsumur="Th";
                            if($y>0){
                                $umur=$y;
                                $sttsumur="Th";
                            }else if($y==0){
                                if($m>0){
                                    $umur=$m;
                                    $sttsumur="Bl";
                                }else if($m==0){
                                    $umur=$d;
                                    $sttsumur="Hr";
                                }
                            }

                            $tanggalupdate=date("Y-m-d H:i:s", ($decode['waktu']/1000));
                            $update = $this->db('booking_registrasi')->where('no_rkm_medis', $pasien['no_rkm_medis'])->where('tanggal_periksa', $referensi['tanggal_periksa'])->update(['status' => 'Terdaftar']);
                            $insert = $this->db('reg_periksa')
                              ->save([
                                'no_reg' => $booking_registrasi['no_reg'],
                                'no_rawat' => $this->core->setNoRawat($booking_registrasi['tanggal_periksa']),
                                'tgl_registrasi' => $booking_registrasi['tanggal_periksa'],
                                'jam_reg' => date('H:i:s'),
                                'kd_dokter' => $booking_registrasi['kd_dokter'],
                                'no_rkm_medis' => $booking_registrasi['no_rkm_medis'],
                                'kd_poli' => $booking_registrasi['kd_poli'],
                                'p_jawab' => $this->core->getPasienInfo('namakeluarga', $booking_registrasi['no_rkm_medis']),
                                'almt_pj' => $this->core->getPasienInfo('alamatpj', $booking_registrasi['no_rkm_medis']),
                                'hubunganpj' => $this->core->getPasienInfo('keluarga', $booking_registrasi['no_rkm_medis']),
                                'biaya_reg' => $_POST['biaya_reg'],
                                'stts' => 'Belum',
                                'stts_daftar' => $_POST['stts_daftar'],
                                'status_lanjut' => 'Ralan',
                                'kd_pj' => $booking_registrasi['kd_pj'],
                                'umurdaftar' => $umur,
                                'sttsumur' => $sttsumur,
                                'status_bayar' => 'Belum Bayar',
                                'status_poli' => $_POST['status_poli']
                              ]);
                            if($insert){
                                $response = array(
                                    'metadata' => array(
                                        'message' => 'Ok',
                                        'code' => 200
                                    )
                                );
                                http_response_code(200);
                            }else{
                                $response = array(
                                    'metadata' => array(
                                        'message' => "Maaf terjadi kesalahan, hubungi Admnistrator..",
                                        'code' => 401
                                    )
                                );
                                http_response_code(401);
                            }
                        }
                    }
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getOperasiRS()
    {
        echo $this->_resultOperasiRS();
        exit();
    }

    private function _resultOperasiRS()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            $data = array();
            $sql = $this->db()->pdo()->prepare("SELECT booking_operasi.no_rawat AS kodebooking, booking_operasi.tanggal AS tanggaloperasi, paket_operasi.nm_perawatan AS jenistindakan,  maping_poli_bpjs.kd_poli_bpjs AS kodepoli, poliklinik.nm_poli AS namapoli, booking_operasi.status AS terlaksana, pasien.no_peserta AS nopeserta FROM pasien, booking_operasi, paket_operasi, reg_periksa, jadwal, poliklinik, maping_poli_bpjs WHERE booking_operasi.no_rawat = reg_periksa.no_rawat AND pasien.no_rkm_medis = reg_periksa.no_rkm_medis AND booking_operasi.kode_paket = paket_operasi.kode_paket AND booking_operasi.kd_dokter = jadwal.kd_dokter AND jadwal.kd_poli = poliklinik.kd_poli AND jadwal.kd_poli=maping_poli_bpjs.kd_poli_rs AND booking_operasi.tanggal BETWEEN '$decode[tanggalawal]' AND '$decode[tanggalakhir]' GROUP BY booking_operasi.no_rawat");
            $sql->execute();
            $sql = $sql->fetchAll();

            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$decode['tanggalawal'])) {
               $errors[] = 'Format tanggal awal jadwal operasi tidak sesuai';
            }
            if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$decode['tanggalakhir'])) {
               $errors[] = 'Format tanggal akhir jadwal operasi tidak sesuai';
            }
            if ($decode['tanggalawal'] > $decode['tanggalakhir']) {
              $errors[] = 'Format tanggal awal harus lebih kecil dari tanggal akhir';
            }
            if(!empty($errors)) {
                foreach($errors as $error) {
                    $response = array(
                        'metadata' => array(
                            'message' => $this->_getErrors($error),
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }
            } else {
                if (count($sql) > 0) {
                    foreach ($sql as $data) {
                        if($data['terlaksana'] == 'Menunggu') {
                          $data['terlaksana'] = 0;
                        } else {
                          $data['terlaksana'] = 1;
                        }
                        $data_array[] = array(
                                'kodebooking' => $data['kodebooking'],
                                'tanggaloperasi' => $data['tanggaloperasi'],
                                'jenistindakan' => $data['jenistindakan'],
                                'kodepoli' => $data['kodepoli'],
                                'namapoli' => $data['namapoli'],
                                'terlaksana' => $data['terlaksana'],
                                'nopeserta' => $data['nopeserta'],
                                'lastupdate' => strtotime(date('Y-m-d H:i:s')) * 1000
                        );
                    }
                    $response = array(
                        'response' => array(
                            'list' => (
                                $data_array
                            )
                        ),
                        'metadata' => array(
                            'message' => 'Ok',
                            'code' => 200
                        )
                    );
                    http_response_code(200);
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Maaf tidak ada daftar booking operasi.',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function getOperasiPasien()
    {
        echo $this->_resultOperasiPasien();
        exit();
    }

    private function _resultOperasiPasien()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $response = array();
        if($header[$this->settings->get('jkn_mobile_v2.header_token')] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header[$this->settings->get('jkn_mobile_v2.header_token')] == $this->_getToken() && $header[$this->settings->get('jkn_mobile_v2.header_username')] == $this->settings->get('jkn_mobile_v2.x_username')) {
            $data = array();
            $cek_nopeserta = $this->db('pasien')->where('no_peserta', $decode['nopeserta'])->oneArray();
            $sql = $this->db()->pdo()->prepare("SELECT booking_operasi.no_rawat AS kodebooking, booking_operasi.tanggal AS tanggaloperasi, paket_operasi.nm_perawatan AS jenistindakan, maping_poli_bpjs.kd_poli_bpjs AS kodepoli, poliklinik.nm_poli AS namapoli, booking_operasi.status AS terlaksana FROM pasien, booking_operasi, paket_operasi, reg_periksa, jadwal, poliklinik, maping_poli_bpjs WHERE booking_operasi.no_rawat = reg_periksa.no_rawat AND pasien.no_rkm_medis = reg_periksa.no_rkm_medis AND booking_operasi.kode_paket = paket_operasi.kode_paket AND booking_operasi.kd_dokter = jadwal.kd_dokter AND jadwal.kd_poli = poliklinik.kd_poli AND jadwal.kd_poli=maping_poli_bpjs.kd_poli_rs AND pasien.no_peserta = '$decode[nopeserta]'  GROUP BY booking_operasi.no_rawat");
            $sql->execute();
            $sql = $sql->fetchAll();

            if($cek_nopeserta == 0) {
               $errors[] = 'Nomor peserta tidak ditemukan';
            }
            if (!empty($decode['nopeserta']) && !ctype_digit($decode['nopeserta']) ){
               $errors[] = 'Nomor kartu harus mengandung angka saja!!';
            }
            if(!empty($errors)) {
                foreach($errors as $error) {
                    $response = array(
                        'metadata' => array(
                            'message' => $this->_getErrors($error),
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }
            } else {
                if ($decode['nopeserta'] != '') {
                    $data_array = [];
                    foreach ($sql as $data) {
                        if($data['terlaksana'] == 'Menunggu') {
                          $data['terlaksana'] = 0;
                        } else {
                          $data['terlaksana'] = 1;
                        }
                        $data_array[] = array(
                                'kodebooking' => $data['kodebooking'],
                                'tanggaloperasi' => $data['tanggaloperasi'],
                                'jenistindakan' => $data['jenistindakan'],
                                'kodepoli' => $data['kodepoli'],
                                'namapoli' => $data['namapoli'],
                                'terlaksana' => $data['terlaksana']
                        );
                    }
                    $response = array(
                        'response' => array(
                            'list' => (
                                $data_array
                            )
                        ),
                        'metadata' => array(
                            'message' => 'Ok',
                            'code' => 200
                        )
                    );
                    http_response_code(200);
                } else {
                    $response = array(
                        'metadata' => array(
                            'message' => 'Maaf tidak ada daftar booking operasi.',
                            'code' => 201
                        )
                    );
                    http_response_code(201);
                }
            }
        } else {
            $response = array(
                'metadata' => array(
                    'message' => 'Access denied',
                    'code' => 201
                )
            );
            http_response_code(201);
        }
        echo json_encode($response);
    }

    public function _getJadwal($kodepoli, $tanggal)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone'));

        $url = $this->bpjsurl.'jadwaldokter/kodepoli/'.$kodepoli.'/tanggal/'.$tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        $code = $json['metadata']['code'];
        $message = $json['metadata']['message'];
        $stringDecrypt = stringDecrypt($key, $json['response']);
        $decompress = '""';
        if(!empty($stringDecrypt)) {
          $decompress = decompress($stringDecrypt);
        }
        if($json != null) {
          echo '{
                  "metaData": {
                      "code": "'.$code.'",
                      "message": "'.$message.'"
                  },
                  "response": '.$decompress.'}';
        } else {
          echo '{
                  "metaData": {
                      "code": "5000",
                      "message": "ERROR"
                  },
                  "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
        }
        exit();
    }

    public function _getUpdateJadwal($data = [])
    {
        $data = [
            'kodepoli' => 'ANA',
            'kodesubspesialis' => '1',
            'kodedokter' => '123456',
            'jadwal' => [
              array(
                'hari' => '1',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '2',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '3',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '4',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '5',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
              array(
                'hari' => '6',
                'buka' => '08:00',
                'tutup' => '12:00'
              ),
            ]
        ];

        $data = json_encode($data);
        $url = $this->bpjsurl.'updatejadwaldokter';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }

    public function _getAntreanAdd()
    {
        header("Refresh:45");
        $slug = parseURL();
        $date = date('Y-m-d');
        $page = 0;
        $offset = 1;
        $perpage = 10;
        $jumlahpasien = 1;
        $jumlahtersimpan = 1;
        $jumlahgagal = 1;
        if(!empty($slug['3'])) {
          $page = $slug['3'];
          $offset = ($page - 1) * $perpage;
        }
        //$date = '2022-01-21';
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }
        $checkAntrian = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim','Sudah')->isNull('keterangan')->limit(10)->toArray();
      	if(!$checkAntrian){
        	$checkAntrian = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim','Belum')->toArray();
        }
        if (!$checkAntrian) {
            # code...
            $exclude_taskid = str_replace(",","','", $this->settings->get('jkn_mobile_v2.exclude_taskid'));
            $query = $this->db()->pdo()->prepare("SELECT pasien.no_peserta,pasien.no_rkm_medis,pasien.no_ktp,pasien.no_tlp,reg_periksa.no_reg,reg_periksa.no_rawat,reg_periksa.tgl_registrasi,reg_periksa.kd_dokter,dokter.nm_dokter,reg_periksa.kd_poli,poliklinik.nm_poli,reg_periksa.stts_daftar,reg_periksa.no_rkm_medis,reg_periksa.kd_pj
            FROM reg_periksa INNER JOIN pasien ON reg_periksa.no_rkm_medis=pasien.no_rkm_medis INNER JOIN dokter ON reg_periksa.kd_dokter=dokter.kd_dokter INNER JOIN poliklinik ON reg_periksa.kd_poli=poliklinik.kd_poli WHERE reg_periksa.tgl_registrasi='$date' AND reg_periksa.kd_poli NOT IN ('$exclude_taskid') AND reg_periksa.stts NOT IN ('Batal','Dirujuk','Dirawat')
            ORDER BY concat(reg_periksa.tgl_registrasi,' ',reg_periksa.jam_reg)");
            $query->execute();
            $query = $query->fetchAll(\PDO::FETCH_ASSOC);;
        }

        //echo "<pre>".print_r($query,true)."</pre>";

        echo 'Menjalankan WS tambah antrian<br>';
        echo '-------------------------------------<br>';

        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];

        foreach ($query as $q) {
            $checkPJ = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();

            if(!$checkPJ) {
                // echo $jumlahgagal.' '.$q['no_rkm_medis'].' '.$q['no_peserta'].'<br>';
                $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $q['kd_poli'])->oneArray();
                $jenispasien = 'NON JKN';
                $jeniskunjungan = 3;
                if($q['kd_pj'] == 'A02' ) {
                    $jenispasien = 'JKN';
                }
              	if($q['kd_pj'] == 'BPJ') {
                  	$jenispasien = 'JKN';
                }

                $nomorreferensi = convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];
                if($jenispasien == 'JKN') {
                    //$jeniskunjungan = 1;
                    $bridging_sep = $this->db('bridging_sep')->where('no_rawat', $q['no_rawat'])->oneArray();
                    $nomorreferensi = $bridging_sep['no_rujukan'];
                    if(!empty($bridging_sep['noskdp'])) {
                        $jeniskunjungan = 3;
                        //$nomorreferensi = $bridging_sep['noskdp'];
                    }
                    /*if(!$bridging_sep) {
                        $bridging_sep_internal = $this->db('bridging_sep_internal')->where('no_rawat', $q['no_rawat'])->oneArray();
                        $nomorreferensi = $bridging_sep_internal['no_rujukan'];
                        if(!empty($bridging_sep_internal['noskdp'])) {
                          $jeniskunjungan = 2;
                          $nomorreferensi = $bridging_sep_internal['noskdp'];
                        }
                    }*/

                }

				$kode_ppk  = $this->settings->get('settings.ppk_bpjs');
                $kodebooking = $kode_ppk.convertNorawat($q['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$q['no_reg'];
                if ($nomorreferensi) {
                    # code...
                    $this->db('mlite_antrian_referensi')->save([
                        'tanggal_periksa' => $q['tgl_registrasi'],
                        'no_rkm_medis' => $q['no_rkm_medis'],
                        'nomor_kartu' => $q['no_peserta'],
                        'nomor_referensi' => $nomorreferensi,
                        'kodebooking' => $kodebooking,
                        'jenis_kunjungan' => $jeniskunjungan,
                        'status_kirim' => 'Belum'
                    ]);
                }
                $checkRM = $this->db('mlite_antrian_referensi')->where('no_rkm_medis', $q['no_rkm_medis'])->where('kodebooking', $kodebooking)->oneArray();
                if ($checkRM) {
                    echo '<br>Berhasil Simpan '.$q['no_rkm_medis'];
                }
            }
        }
        $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->isNull('keterangan')->limit(10)->toArray();
        if (!$referensi) {
            # code...
            $referensi = $this->db('mlite_antrian_referensi')->where('tanggal_periksa', $date)->where('status_kirim','Belum')->limit(10)->toArray();
        }

        foreach ($referensi as $value) {
            echo 'Menjalankan Add Antrean<br>';
            date_default_timezone_set($this->settings->get('settings.timezone'));
            $reg_periksa = $this->db('reg_periksa')->select(['kd_pj' => 'reg_periksa.kd_pj','kd_dokter' => 'reg_periksa.kd_dokter','kd_poli' => 'reg_periksa.kd_poli','no_reg' => 'reg_periksa.no_reg','stts_daftar' => 'reg_periksa.stts_daftar','no_ktp' => 'pasien.no_ktp','no_tlp' => 'pasien.no_tlp','no_rawat' => 'reg_periksa.no_rawat','tgl_registrasi' => 'reg_periksa.tgl_registrasi'])->join('pasien','pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.tgl_registrasi', $date)->where('reg_periksa.kd_poli','!=','IGD01')->where('pasien.no_peserta', $value['nomor_kartu'])->where('pasien.no_rkm_medis',$value['no_rkm_medis'])->oneArray();
            $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
            $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
            $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();
            $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
            $minutes = $no_urut_reg * 10;
            $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));
            $jenispasien = 'NON JKN';
            if($reg_periksa['kd_pj'] == 'A02') {
                $jenispasien = 'JKN';
            }
          	if($reg_periksa['kd_pj'] == 'BPJ') {
                $jenispasien = 'JKN';
            }
          	if($reg_periksa['kd_pj'] == 'A01') {
                $jenispasien = 'NON JKN';
            }
            $pasienbaru = '1';
            if($reg_periksa['stts_daftar'] == 'Lama') {
                $pasienbaru = '0';
            }

            $nomorkartu = $value['nomor_kartu'];
            $nik = $reg_periksa['no_ktp'];
            $nohp = $reg_periksa['no_tlp'];
            if(empty($reg_periksa['no_tlp'])) {
                $nohp = '0000000000';
            }

            if($jenispasien == 'NON JKN') {
                $nik = '';
                $nomorkartu = '';
                $nohp = '';
            }
            $kodebooking = $value['kodebooking'];
            if (!$kodebooking) {
                $kodebooking = convertNorawat($reg_periksa['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$reg_periksa['no_reg'];
            }
            $jampraktek = substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5);
            if ($jampraktek == '-') {
                $jampraktek = '08:00-10:00';
            }
            $data = [
                'kodebooking' => $kodebooking,
                'jenispasien' => $jenispasien,
                'nomorkartu' => $nomorkartu,
                'nik' => $nik,
                'nohp' => $nohp,
                'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
                'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
                'pasienbaru' => $pasienbaru,
                'norm' => $value['no_rkm_medis'],
                'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
                'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
                'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
                'jampraktek' => $jampraktek,
                'jeniskunjungan' => $value['jenis_kunjungan'],
                'nomorreferensi' => $value['nomor_referensi'],
                'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
                'angkaantrean' => $reg_periksa['no_reg'],
                'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kouta['jam_mulai']) * 1000,
                'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                'kuotajkn' => intval($jadwaldokter['kuota']),
                'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                'kuotanonjkn' => intval($jadwaldokter['kuota']),
                'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
            ];
            echo 'Request:<br>';
            echo "<pre>".print_r($data,true)."</pre>";
            $data = json_encode($data);
            $url = $this->bpjsurl.'antrean/add';
            $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
            $data = json_decode($output, true);
            echo 'Response:<br>';
            echo json_encode($data);
            echo '</br>';
          	echo $reg_periksa['kd_pj'].' poli '.$maping_poli_bpjs['nm_poli_bpjs'].' & '.$reg_periksa['kd_poli'].'</br>';
            echo $data['metadata']['code'];
            if($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208){
                if (!$value['kodebooking']) {
                    $this->db('mlite_antrian_referensi')->where('nomor_referensi', $value['nomor_referensi'])->save([
                        'kodebooking' => $kodebooking,
                        'status_kirim' => 'Sudah',
                        'keterangan' => $data['metadata']['message']
                    ]);
                } else {
                    $this->db('mlite_antrian_referensi')->where('nomor_referensi', $value['nomor_referensi'])->save([
                        'status_kirim' => 'Sudah',
                        'keterangan' => $data['metadata']['message']
                    ]);
                }
            }
            if($data['metadata']['code'] == 201){
                $this->db('mlite_antrian_referensi')->where('nomor_referensi', $value['nomor_referensi'])->save([
                    'status_kirim' => 'Gagal',
                    'keterangan' => $data['metadata']['message']
                ]);
            }
            echo '<br>-----------------------------------------------------------------------------------------------------------------------------<br><br>';
        }
        exit();
    }

    public function _getAntreanBatal()
    {
        $date = date('Y-m-d');
        $query = $this->db('mlite_antrian_referensi_batal')
          ->where('tanggal_batal', $date)
          ->toArray();

        echo 'Menjalankan WS batal antrian Mobile JKN BPJS<br>';
        echo '-------------------------------------<br>';

        foreach ($query as $q) {
            // if($mutasi_berkas){
                $data = [
                    'kodebooking' => $q['nomor_referensi'],
                    'keterangan' => $q['keterangan']
                ];
                $data = json_encode($data);
                echo 'Request:<br>';
                echo $data;

                echo '<br>';
                $url = $this->bpjsurl.'antrean/batal';
                $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                $json = json_decode($output, true);
                echo 'Response:<br>';
                echo json_encode($json);

                echo '<br>-------------------------------------<br><br>';
            // }
        }

        exit();
    }

    public function _getAntreanUpdateWaktu($page = 1)
    {
        header("Refresh:35");
        $date = date('Y-m-d');
        $task = '';
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
          $date = $_GET['tgl'];
        }
        if(isset($_GET['task']) && $_GET['task'] !='') {
          $task = $_GET['task'];
        }

        $slug = parseURL();
        $page = '';
        $offset = 1;
      	$perpage = 10;
        if(!empty($slug['3'])) {
          $page = $slug['3'];
          $offset = ($page - 1) * $perpage;
        }
        $query = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa',$date)->where('nomor_referensi','!=','')->where('status','Belum')->isNull('keterangan')->limit(5)->toArray();
        if (!$query) {
            # code...
            $sql = "SELECT * FROM mlite_antrian_referensi WHERE kodebooking NOT IN (SELECT nomor_referensi FROM mlite_antrian_referensi_taskid WHERE tanggal_periksa =  '{$date}' AND taskid IN ('1','2','3','4','5','6','7')) AND tanggal_periksa =  '{$date}'  AND status_kirim = 'Sudah' LIMIT $perpage";
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute();
            $query = $stmt->fetchAll();

            echo 'Menjalankan WS taskid (1) mulai tunggu admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 1)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                        $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                    if($mlite_antrian_loket){
                        date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 1,
                            'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 1)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 1 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (2) mulai pelayanan admisi<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 2)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                    if($mlite_antrian_loket){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 2,
                            'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 2)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 2 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (3) mulai tunggu poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', 3)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    /*if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }*/
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mutasi_berkas = $this->db('mutasi_berkas')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                    if($mutasi_berkas){
                            echo 'A ';
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 3,
                            'waktu' => strtotime($mutasi_berkas['dikirim']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 3)->oneArray();
                        if ($checkSimpan) {
                            echo $mutasi_berkas['dikirim'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    } else if (!$mutasi_berkas){
                        //$reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                        echo 'B '.$q['no_rkm_medis'].' '.$date.' ';
                        echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'];
                        date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 3,
                            'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],5,10)) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 3)->oneArray();
                        if ($checkSimpan) {
                            echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Reg'.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (4) mulai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 4)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    /*if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }*/
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $mutasi_berkas = $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $reg_periksa['no_rawat'])->where('diterima', '<>', '0000-00-00 00:00:00')->oneArray();
                    if($mutasi_berkas){
                        echo 'A';
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 4,
                            'waktu' => strtotime($mutasi_berkas['diterima']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                        if ($checkSimpan) {
                            echo $mutasi_berkas['diterima'].' Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Terima'.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    } else if (!$mutasi_berkas){
                        $mutasi_berkas = $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                        if($mutasi_berkas){
                            echo 'B';
                                date_default_timezone_set($this->settings->get('settings.timezone'));
                                $this->db('mlite_antrian_referensi_taskid')
                            ->save([
                                'tanggal_periksa' => $date,
                                'nomor_referensi' => $q['kodebooking'],
                                'taskid' => 4,
                                'waktu' => strtotime($this->randMinutes($mutasi_berkas['dikirim'],10,15)) * 1000,
                                'status' => 'Belum'
                            ]);
                            $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                            if ($checkSimpan) {
                                echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim'.$checkSimpan['waktu'];
                            }
                            echo '<br>-------------------------------------<br><br>';
                        } else if (!$mutasi_berkas){
                            echo 'C';
                            $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                                $this->db('mlite_antrian_referensi_taskid')
                            ->save([
                                'tanggal_periksa' => $date,
                                'nomor_referensi' => $q['kodebooking'],
                                'taskid' => 4,
                                'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],10,15)) * 1000,
                                'status' => 'Belum'
                            ]);
                            $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                            if ($checkSimpan) {
                                echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                            }
                            echo '<br>-------------------------------------<br><br>';
                        }
                    }
                }
            }

            echo 'Menjalankan WS taskid (5) selesai pelayanan poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 5)->oneArray()) {
                    $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                    $q['no_rkm_medis'] = $q['no_rkm_medis'];
                    /*if($pasien) {
                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                    }*/
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                    if($pemeriksaan_ralan){
                        echo 'A';
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 5,
                            'waktu' => strtotime($pemeriksaan_ralan['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    } else {
                        $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                        date_default_timezone_set($this->settings->get('settings.timezone'));
                        if ($resep_obat) {
                            echo 'B';
                            # code...
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 5,
                            'waktu' => strtotime($this->randMinutesMinus($resep_obat['datajam'])) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                        } else {
                            echo 'C';
                            $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                                $this->db('mlite_antrian_referensi_taskid')
                            ->save([
                                'tanggal_periksa' => $date,
                                'nomor_referensi' => $q['kodebooking'],
                                'taskid' => 5,
                                'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],15,20)) * 1000,
                                'status' => 'Belum'
                            ]);
                            $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                            if ($checkSimpan) {
                                echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                            }
                            echo '<br>-------------------------------------<br><br>';
                        }
                    }
                }
            }

            echo 'Menjalankan WS taskid (6) permintaan resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 6)->oneArray()) {
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();

                    if($resep_obat){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 6,
                            'waktu' => strtotime($resep_obat['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 6)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 6 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }

            echo 'Menjalankan WS taskid (7) validasi resep poli<br>';
            echo '-------------------------------------<br>';

            foreach ($query as $q) {
                if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('status','Sudah')->where('taskid', 7)->oneArray()) {
                    $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $reg_periksa['no_rawat'])->where('concat(tgl_perawatan," ",jam)', '<>', 'concat(tgl_peresepan," ",jam_peresepan)')->oneArray();
                    if($resep_obat){
                            date_default_timezone_set($this->settings->get('settings.timezone'));
                            $this->db('mlite_antrian_referensi_taskid')
                        ->save([
                            'tanggal_periksa' => $date,
                            'nomor_referensi' => $q['kodebooking'],
                            'taskid' => 7,
                            'waktu' => strtotime($resep_obat['datajam']) * 1000,
                            'status' => 'Belum'
                        ]);
                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 7)->oneArray();
                        if ($checkSimpan) {
                            echo 'Berhasil Simpan Task Id 7 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }
        } else { // Ketika Gagal Kirim , Kirim Lagi *****************************************************************
            if ($task == '') {
                # code...
                echo 'Menjalankan WS taskid (1) mulai tunggu admisi<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
    
                    $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)->oneArray();
                    if($mlite_antrian_loket){
    
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => 1,
                            'waktu' => $q['waktu'],
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 1)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
    
                }
    
                echo 'Menjalankan WS taskid (2) mulai pelayanan admisi<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
    
                    $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)->oneArray();
                    if($mlite_antrian_loket){
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => 2,
                            'waktu' => $q['waktu']
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 2)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
    
                }
    
                echo 'Menjalankan WS taskid (3) mulai tunggu poli<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
    
                    $mutasi_berkas = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)->oneArray();
                    if($mutasi_berkas){
                        $data = [
                            'kodebooking' => $mutasi_berkas['nomor_referensi'],
                            'taskid' => 3,
                            'waktu' => $mutasi_berkas['waktu']
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 3)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
    
                }
    
                echo 'Menjalankan WS taskid (4) mulai pelayanan poli<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
    
                    $mutasi_berkas = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)->oneArray();
                    if($mutasi_berkas){
                        $data = [
                            'kodebooking' => $mutasi_berkas['nomor_referensi'],
                            'taskid' => 4,
                            'waktu' => $mutasi_berkas['waktu']
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 4)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
    
                echo 'Menjalankan WS taskid (5) selesai pelayanan poli<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
    
                    $pemeriksaan_ralan = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)->oneArray();
                    if($pemeriksaan_ralan){
                        $data = [
                            'kodebooking' => $pemeriksaan_ralan['nomor_referensi'],
                            'taskid' => 5,
                            'waktu' => $pemeriksaan_ralan['waktu']
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 5)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
    
                }
    
                echo 'Menjalankan WS taskid (6) permintaan resep poli<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
                    $resep_obat = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)->oneArray();
    
                    if($resep_obat){
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => 6,
                            'waktu' => $q['waktu'],
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 6)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
    
                echo 'Menjalankan WS taskid (7) validasi resep poli<br>';
                echo '-------------------------------------<br>';
    
                foreach ($query as $q) {
                    $resep_obat = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)->oneArray();
                    if($resep_obat){
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => 7,
                            'waktu' => $q['waktu']
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', 7)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            } else {
                echo 'Menjalankan WS taskid ('.$task.') mulai tunggu admisi<br>';
                echo '-------------------------------------<br>';
    
                $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('taskid', $task)->limit(5)->toArray();
                if($mlite_antrian_loket){
                    foreach ($mlite_antrian_loket as $q) {
                        # code...
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => $task,
                            'waktu' => $q['waktu'],
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        $jsonencode = json_encode($json);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $jsonencode == null){
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
            }
        }
        exit();
    }

    public function randMinutes($date1,$minx,$maxx){
        date_default_timezone_set($this->settings->get('settings.timezone'));
        // $format = 'Y-m-d H:i:s';
        // $date = \DateTime::createFromFormat($format, $date1,new \DateTimeZone($this->settings->get('settings.timezone')));
        $time = date("H:i:s", strtotime($date1));
        $date = date("Y-m-d", strtotime($date1));
        list($h, $m, $s) = explode(":", $time);
        $seconds = $s + ($m * 60) + ($h * 3600);
        $min = $minx * 60;
        $max = $maxx * 60;
        $seconds += rand($min, $max); //set desired min and max values

        // now back to time format
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);

        $timeFormat1 = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        $timeFormat = $date.' '.$timeFormat1;
        return $timeFormat;
    }

    public function randMinutesMinus($date1){
        date_default_timezone_set($this->settings->get('settings.timezone'));
        // $format = 'Y-m-d H:i:s';
        // $date = \DateTime::createFromFormat($format, $date1);
        $time = date("H:i:s", strtotime($date1));
        $date = date("Y-m-d", strtotime($date1));
        list($h, $m, $s) = explode(":", $time);
        $seconds = $s + ($m * 60) + ($h * 3600);
        $min = 5 * 60;
        $max = 10 * 60;
        $seconds -= rand($min, $max); //set desired min and max values

        // now back to time format
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);

        $timeFormat1 = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        $timeFormat = $date.' '.$timeFormat1;
        return $timeFormat;
    }

    public function _getAntreanGetListTask()
    {
        $slug = parseURL();
        $data = [
            'kodebooking' => $slug['3']
        ];

        $data = json_encode($data);
        $url = $this->bpjsurl.'antrean/getlisttask';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }

    public function _getAntreanWaktuTunggu()
    {
        $slug = parseURL();
        $url = $this->bpjsurl.'dashboard/waktutunggu/bulan/'.$slug[3].'/tahun/'.$slug[4].'/waktu/'.$slug[5];
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }

    public function _getAntreanWaktuTungguTanggal()
    {
        $slug = parseURL();
        $url = $this->bpjsurl.'dashboard/waktutunggu/tanggal/'.$slug[3].'/waktu/'.$slug[4];
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }

    private function _getToken()
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(['username' => $this->settings->get('jkn_mobile_v2.x_username'), 'password' => $this->settings->get('jkn_mobile_v2.x_password'), 'date' => strtotime(date('Y-m-d')) * 1000]);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }

    private function _getErrors($error)
    {
        $errors = $error;
        return $errors;
    }

    private function _setUmur($tanggal)
    {
        list($cY, $cm, $cd) = explode('-', date('Y-m-d'));
        list($Y, $m, $d) = explode('-', date('Y-m-d', strtotime($tanggal)));
        $umur = $cY - $Y;
        return $umur;
    }
  
  	public function getAplicare()
    {
        $slug = parseURL();
        echo $this->_resultBed($slug[2]);
        exit();
    }

    private function checkBed($kelas){
        $bed = array();
        $sql = "SELECT bangsal.nm_bangsal ,kamar.kd_bangsal, COUNT(kamar.kd_kamar) as jml , SUM(IF(kamar.status = 'ISI',1,0)) as isi , SUM(IF(kamar.status = 'KOSONG',1,0)) as kosong  FROM kamar JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal WHERE kamar.statusdata = '1'";
        if ($kelas == 'cov') {
            $sql .= " AND kamar.kd_kamar LIKE '%cov%' ";
        } else if ($kelas == 'icu') {
            $sql .= " AND kamar.kd_bangsal = 'B0007' ";
        } else if ($kelas == 'nicu') {
            $sql .= " AND kamar.kd_bangsal = 'B0006'  AND kamar.kd_kamar LIKE '%nicu%' ";
        } else if ($kelas == 'peri') {
            $sql .= " AND kamar.kd_bangsal = 'B0006'  AND kamar.kd_kamar LIKE '%peri%' ";
        } else if ($kelas == 'hcu') {
            $sql .= " AND kamar.kd_kamar LIKE '%HCU%' ";
        } else if ($kelas == 'picu') {
            $sql .= " AND kamar.kd_bangsal = 'B0008' ";
        } else if ($kelas == 'iso') {
            $sql .= " AND kamar.kd_bangsal != 'B0103' AND kamar.kd_kamar LIKE '%iso%' ";
        } else {
            $sql .= " AND kamar.kelas = 'Kelas $kelas' AND kamar.kd_bangsal NOT IN ('B0012','B0008','B0006','B0007') AND kamar.kd_kamar NOT LIKE '%HCU%' AND kamar.kd_kamar NOT LIKE '%ISO%' ";
        }
        $sql .= " GROUP BY kamar.kd_bangsal ";
        $query = $this->db()->pdo()->prepare($sql);
        $query->execute();
        $bedlist = $query->fetchAll();
        foreach ($bedlist as $value) {
            switch ($kelas) {
                case '1':
                    $value['kelas'] = 'KL1';
                    break;
                case '2':
                    $value['kelas'] = 'KL2';
                    break;
                case '3':
                    $value['kelas'] = 'KL3';
                    break;
                case 'vip':
                    $value['kelas'] = 'VIP';
                    break;
                case 'icu':
                    $value['kelas'] = 'ICU';
                    break;
                case 'nicu':
                    $value['kelas'] = 'NIC';
                    break;
                case 'picu':
                    $value['kelas'] = 'PIC';
                    break;
                case 'hcu':
                    $value['kelas'] = 'HCU';
                    break;

                default:
                    # code...
                    break;
            }
            $bed[] = $value;
        }
        return $bed;
    }

    private function _resultBed($slug)
    {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $kode_ppk  = $this->settings->get('settings.ppk_bpjs');
        $url = "https://new-api.bpjs-kesehatan.go.id";
        if ($slug == 'listkamar') {
            $url .= "/aplicaresws/rest/bed/read/".$kode_ppk."/1/100";
            $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
            echo $output;
        }
        if ($slug != 'listkamar') {
            $bed = $this->checkBed($slug);
            $url .= "/aplicaresws/rest/bed/update/".$kode_ppk;
            foreach ($bed as $value) {
          	$_tersedia = $value['jml'] - $value['isi'];
            $tersedia = $_tersedia;
            if($_tersedia == '0') {
              $tersedia = '1';
            }
                $data = array(
                    'kodekelas'=>$value['kelas'],
                    'koderuang'=>$value['kd_bangsal'],
                    'namaruang'=>$value['nm_bangsal'],
                    'kapasitas'=>$value['jml'],
                    'tersedia'=>$tersedia,
                    'tersediapria'=>"0",
                    'tersediawanita'=>"0",
                    'tersediapriawanita'=>$value['isi']
                );
                // $data = array(
                //     'kodekelas'=>"KL2",
                //     'koderuang'=>"B0010"
                // );
                $postdata = json_encode($data);
                $output = BpjsService::postAplicare($url, $postdata, $this->consid, $this->secretkey, $this->user_key, $tStamp);
                echo $output;
            }
        }
        // $url .= "/aplicaresws/rest/ref/kelas";
        // $url .= "/aplicaresws/rest/bed/update/1708R008";
        // $url .= "/aplicaresws/rest/bed/create/1708R008";
        // $url .= "/aplicaresws/rest/bed/delete/1708R008";
        exit();
    }
  
  	private function _getTokenManual(){
        $token = 'asuKabeh1#';
        return $token;
    }

    public function _postMjknAdd(){
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $date = date('Y-m-d');
      	//$date = '2023-06-19';
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];
        if($header['X-Header-Token'] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header['X-Header-Token'] == $this->_getTokenManual()){
            $checkAntrian = $this->db('mlite_antrian_referensi')->where('no_rkm_medis',$decode['no_rkm_medis'])->where('nomor_kartu',$decode['nomor_kartu'])->where('tanggal_periksa',$date)->oneArray();
            if ($checkAntrian) {
                $reg_periksa = $this->db('reg_periksa')->select(['kd_pj' => 'reg_periksa.kd_pj','kd_dokter' => 'reg_periksa.kd_dokter','kd_poli' => 'reg_periksa.kd_poli','no_reg' => 'reg_periksa.no_reg','stts_daftar' => 'reg_periksa.stts_daftar','no_ktp' => 'pasien.no_ktp','no_tlp' => 'pasien.no_tlp','no_rawat' => 'reg_periksa.no_rawat','tgl_registrasi' => 'reg_periksa.tgl_registrasi'])->join('pasien','pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.tgl_registrasi', $date)->where('pasien.no_peserta', $decode['nomor_kartu'])->oneArray();
                $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
                $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
                $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();
                $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
                $minutes = $no_urut_reg * 10;
                $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));
                $jenispasien = 'NON JKN';
                if($reg_periksa['kd_pj'] == 'A02') {
                    $jenispasien = 'JKN';
                }
                  if($reg_periksa['kd_pj'] == 'BPJ') {
                    $jenispasien = 'JKN';
                }
                  if($reg_periksa['kd_pj'] == 'A01') {
                    $jenispasien = 'NON JKN';
                }
                $pasienbaru = '1';
                if($reg_periksa['stts_daftar'] == 'Lama') {
                    $pasienbaru = '0';
                }
    
                $nomorkartu = $decode['nomor_kartu'];
                $nik = $reg_periksa['no_ktp'];
                $nohp = $reg_periksa['no_tlp'];
                if(empty($reg_periksa['no_tlp'])) {
                    $nohp = '0000000000';
                }
    
                if($jenispasien == 'NON JKN') {
                    $nik = '';
                    $nomorkartu = '';
                    $nohp = '';
                }
                $kodebooking = $checkAntrian['kodebooking'];
                // if (!$kodebooking) {
                //     $kodebooking = convertNorawat($reg_periksa['no_rawat']).''.$maping_poli_bpjs['kd_poli_bpjs'].''.$reg_periksa['no_reg'];
                // }
                $jampraktek = substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5);
                if ($jampraktek == '-') {
                    $jampraktek = '08:00-10:00';
                }
                $data = [
                    'kodebooking' => $kodebooking,
                    'jenispasien' => $jenispasien,
                    'nomorkartu' => $nomorkartu,
                    'nik' => $nik,
                    'nohp' => $nohp,
                    'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
                    'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
                    'pasienbaru' => $pasienbaru,
                    'norm' => $checkAntrian['no_rkm_medis'],
                    'tanggalperiksa' => $reg_periksa['tgl_registrasi'],
                    'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
                    'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
                    'jampraktek' => $jampraktek,
                    'jeniskunjungan' => $checkAntrian['jenis_kunjungan'],
                    'nomorreferensi' => $checkAntrian['nomor_referensi'],
                    'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
                    'angkaantrean' => $reg_periksa['no_reg'],
                    'estimasidilayani' => strtotime($reg_periksa['tgl_registrasi'].' '.$cek_kouta['jam_mulai']) * 1000,
                    'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                    'kuotajkn' => intval($jadwaldokter['kuota']),
                    'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                    'kuotanonjkn' => intval($jadwaldokter['kuota']),
                    'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
                ];
                $data = json_encode($data);
                $url = $this->bpjsurl.'antrean/add';
                $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                $data = json_decode($output, true);
                if($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208){
                    $this->db('mlite_antrian_referensi')->where('nomor_referensi', $checkAntrian['nomor_referensi'])->save([
                        'status_kirim' => 'Sudah',
                        'keterangan' => $data['metadata']['code'].' '.$data['metadata']['message']
                    ]);
                    $response = array(
                        'metadata' => array(
                            'message' => $data['metadata']['message'],
                            'code' => $data['metadata']['code']
                        ),
                        'response' => 'NULL'
                    );
                    http_response_code($data['metadata']['code']);
                }
                if($data['metadata']['code'] == 201){
                    $this->db('mlite_antrian_referensi')->where('nomor_referensi', $checkAntrian['nomor_referensi'])->save([
                        'status_kirim' => 'Gagal',
                        'keterangan' => $data['metadata']['message']
                    ]);
                    $response = array(
                        'metadata' => array(
                            'message' => $data['metadata']['message'],
                            'code' => 201
                        ),
                        'response' => 'NULL'
                    );
                    http_response_code(201);
                }
            }
          	if(!$checkAntrian){
            	$response = array(
                    'metadata' => array(
                        'message' => 'Tidak Ada Data pada tanggal : '.$date.' untuk pasien '.$decode['no_rkm_medis'] ,
                        'code' => 201
                    ),
                    'response' => 'NULL'
                );
                http_response_code(201);
            }
        }
      echo json_encode($response);
      exit();
    }
  
  	public function _getJadwalSimrs()
    {
        header("Content-Type: application/json");
        $header = apache_request_headers();
        $date = date('Y-m-d');
      	//$date = '2023-06-19';
        $konten = trim(file_get_contents("php://input"));
        $decode = json_decode($konten, true);
        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];
        if($header['X-Header-Token'] == false) {
            $response = array(
                'metadata' => array(
                    'message' => 'Token expired',
                    'code' => 201
                )
            );
            http_response_code(201);
        } else if ($header['X-Header-Token'] == $this->_getTokenManual()){
            $jadwal = $this->db('jadwal')->select('nm_dokter')->join('dokter','dokter.kd_dokter = jadwal.kd_dokter')->join('poliklinik','poliklinik.kd_poli = jadwal.kd_poli')->where('hari_kerja',$hari)->where('kuota','!=','0')->where('jadwal.kd_poli','!=','IGDK')->like('poliklinik.nm_poli','%pagi%')->toArray();
            $response = array(
                'metadata' => array(
                    'message' => "Ok",
                    'code' => 200
                ),
                 'response' => array(
                    'list' => $jadwal
                )
            );
            http_response_code(200);
        }
        echo json_encode($response);
        exit();
    }
  
  	public function _postMjknManual($no_rkm_medis,$tanggal){
        $date = $tanggal;
        $tentukan_hari=date('D',strtotime($date));
        $day = array(
          'Sun' => 'AKHAD',
          'Mon' => 'SENIN',
          'Tue' => 'SELASA',
          'Wed' => 'RABU',
          'Thu' => 'KAMIS',
          'Fri' => 'JUMAT',
          'Sat' => 'SABTU'
        );
        $hari=$day[$tentukan_hari];
        $checkAntrian = $this->db('mlite_antrian_referensi')->where('no_rkm_medis',$no_rkm_medis)->where('tanggal_periksa',$date)->oneArray();
        if ($checkAntrian) {
            // $reg_periksa = $this->db('reg_periksa')->select(['kd_pj' => 'reg_periksa.kd_pj','kd_dokter' => 'reg_periksa.kd_dokter','kd_poli' => 'reg_periksa.kd_poli','no_reg' => 'reg_periksa.no_reg','stts_daftar' => 'reg_periksa.stts_daftar','no_ktp' => 'pasien.no_ktp','no_tlp' => 'pasien.no_tlp','no_rawat' => 'reg_periksa.no_rawat','tgl_registrasi' => 'reg_periksa.tgl_registrasi'])->join('pasien','pasien.no_rkm_medis = reg_periksa.no_rkm_medis')->where('reg_periksa.tgl_registrasi', $date)->where('pasien.no_peserta', $decode['nomor_kartu'])->oneArray();
            $reg_periksa = $this->db('booking_registrasi')->join('pasien','pasien.no_rkm_medis = booking_registrasi.no_rkm_medis')->where('booking_registrasi.no_rkm_medis',$no_rkm_medis)->where('booking_registrasi.tanggal_periksa',$date)->oneArray();
            $maping_dokter_dpjpvclaim = $this->db('maping_dokter_dpjpvclaim')->where('kd_dokter', $reg_periksa['kd_dokter'])->oneArray();
            $maping_poli_bpjs = $this->db('maping_poli_bpjs')->where('kd_poli_rs', $reg_periksa['kd_poli'])->oneArray();
            $jadwaldokter = $this->db('jadwal')->where('kd_dokter', $reg_periksa['kd_dokter'])->where('kd_poli', $reg_periksa['kd_poli'])->where('hari_kerja', $hari)->oneArray();
            $no_urut_reg = substr($reg_periksa['no_reg'], 0, 3);
            $minutes = $no_urut_reg * 10;
            $cek_kouta['jam_mulai'] = date('H:i:s',strtotime('+'.$minutes.' minutes',strtotime($jadwaldokter['jam_mulai'])));

            $jenispasien = 'JKN';
            $pasienbaru = '0';

            $nomorkartu = $checkAntrian['nomor_kartu'];
            $nik = $reg_periksa['no_ktp'];
            $nohp = $reg_periksa['no_tlp'];
            if(empty($reg_periksa['no_tlp'])) {
                $nohp = '0000000000';
            }

            $kodebooking = $checkAntrian['kodebooking'].date('his').'CHG';

            $jampraktek = substr($jadwaldokter['jam_mulai'],0,5).'-'.substr($jadwaldokter['jam_selesai'],0,5);
            if ($jampraktek == '-') {
                $jampraktek = '08:00-10:00';
            }
            $data = [
                'kodebooking' => $kodebooking,
                'jenispasien' => $jenispasien,
                'nomorkartu' => $nomorkartu,
                'nik' => $nik,
                'nohp' => $nohp,
                'kodepoli' => $maping_poli_bpjs['kd_poli_bpjs'],
                'namapoli' => $maping_poli_bpjs['nm_poli_bpjs'],
                'pasienbaru' => $pasienbaru,
                'norm' => $checkAntrian['no_rkm_medis'],
                'tanggalperiksa' => $checkAntrian['tanggal_periksa'],
                'kodedokter' => $maping_dokter_dpjpvclaim['kd_dokter_bpjs'],
                'namadokter' => $maping_dokter_dpjpvclaim['nm_dokter_bpjs'],
                'jampraktek' => $jampraktek,
                'jeniskunjungan' => $checkAntrian['jenis_kunjungan'],
                'nomorreferensi' => $checkAntrian['nomor_referensi'],
                'nomorantrean' => $maping_poli_bpjs['kd_poli_bpjs'].'-'.$reg_periksa['no_reg'],
                'angkaantrean' => $reg_periksa['no_reg'],
                'estimasidilayani' => strtotime($checkAntrian['tanggal_periksa'].' '.$cek_kouta['jam_mulai']) * 1000,
                'sisakuotajkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                'kuotajkn' => intval($jadwaldokter['kuota']),
                'sisakuotanonjkn' => $jadwaldokter['kuota']-ltrim($reg_periksa['no_reg'],'0'),
                'kuotanonjkn' => intval($jadwaldokter['kuota']),
                'keterangan' => 'Peserta harap 30 menit lebih awal guna pencatatan administrasi.'
            ];
            $data = json_encode($data);
            // echo $data;
            $url = $this->bpjsurl.'antrean/add';
            $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
            $data = json_decode($output, true);
            if($data['metadata']['code'] == 200 || $data['metadata']['code'] == 208){
                $this->db('mlite_antrian_referensi')->where('nomor_referensi', $checkAntrian['nomor_referensi'])->where('tanggal_periksa',$date)->save([
                    'kodebooking' => $kodebooking,
                ]);
            }
        }
        echo json_encode($data);
        exit();
    }

    function getListAntrol($tanggal) {
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime("1970-01-01 00:00:00"));
        $key = $this->consid.$this->secretkey.$tStamp;
        date_default_timezone_set($this->settings->get('settings.timezone'));
        $url = $this->bpjsurl.'antrean/pendaftaran/tanggal/'.$tanggal;
        $output = BpjsService::get($url, NULL, $this->consid, $this->secretkey, $this->user_key, $tStamp);
        $json = json_decode($output, true);
        $code = $json['metadata']['code'];
        $message = $json['metadata']['message'];
        $decompress = '""';
        if ($code == 200) {
            # code...
            $stringDecrypt = stringDecrypt($key, $json['response']);
            if(!empty($stringDecrypt)) {
              $decompress = decompress($stringDecrypt);
            }
        }
        if($json != null) {
          echo '{
                  "metaData": {
                      "code": "'.$code.'",
                      "message": "'.$message.'"
                  },
                  "response": '.$decompress.'}';
        } else {
          echo '{
                  "metaData": {
                      "code": "5000",
                      "message": "ERROR"
                  },
                  "response": "ADA KESALAHAN ATAU SAMBUNGAN KE SERVER BPJS TERPUTUS."}';
        }
        exit();

    }

    public function postBatalManual($kodebooking) {
        $data = [
            'kodebooking' => $kodebooking,
            'keterangan' => 'Perubahan Jadwal Dokter'
        ];
        $data = json_encode($data);
        $url = $this->bpjsurl.'antrean/batal';
        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
        $json = json_decode($output, true);
        echo json_encode($json);
        exit();
    }
  
  	public function _getAntreanUpdateWaktu2($offsethalaman = 0,$update = 'false')
    {   
        $task = 1;
        if(isset($_GET['task']) && $_GET['task'] !='') {
            $task = $_GET['task'];
        }
        $date = date('Y-m-d');
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
            $date = $_GET['tgl'];
        }
        $offsethalaman = $offsethalaman + 1;
        $time = date('H:i:s');
        $limit = 10;
        $no = 1;
        $offset = $offsethalaman * $limit;

        if ($update == 'false') {
            $sql = "SELECT * FROM mlite_antrian_referensi WHERE tanggal_periksa = '$date' and status_kirim = 'Sudah' AND keterangan = 'Ok.' LIMIT ".$limit." OFFSET ".$offset;
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute();
            $query = $stmt->fetchAll();
        }
        if ($update == 'true') {
            $query = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa',$date)->where('nomor_referensi','!=','')->where('status','Belum')->where('taskid', $task)->isNull('keterangan')->limit(2)->toArray();
        }

        echo 'Menjalankan WS taskid '.$task.'<br>';
        echo 'Halaman : '.$offsethalaman.'<br>';
        echo 'Tanggal : '.$date.' '.$time.'<br>';
        if ($task == 8) {
            echo 'Selesai Kirim<br>';
        }
        echo '-------------------------------------<br>';
        if ($query) {
            foreach ($query as $q) {
                if ($update == 'true') {
                    $offsethalaman = 1;
                    $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'/true?task='.$task);
                    $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)->oneArray();
                    if($mlite_antrian_loket){
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => $task,
                            'waktu' => $q['waktu'],
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $ket = $json['metadata']['message'];
                            if ($json == null) {
                                $ket = 'Ok.';
                            }
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $ket
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
                if ($update == 'false') {
                    switch ($task) {
                        case '1':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                                if($mlite_antrian_loket){
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $no.'. Berhasil Simpan Task Id '.$task.' No Rekam Medis '.$q['no_rkm_medis'].' dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            }
                            break;
                        case '2':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                                if($mlite_antrian_loket){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $no.'. Berhasil Simpan Task Id '.$task.'  No Rekam Medis '.$q['no_rkm_medis'].' dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            }
                            break;
                        case '3':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mutasi_berkas = $this->db('mutasi_berkas')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                                if($mutasi_berkas){
                                        echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mutasi_berkas['dikirim']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $mutasi_berkas['dikirim'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$mutasi_berkas){
                                    echo 'B '.$q['no_rkm_medis'].' '.$date.' ';
                                    echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'];
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],5,10)) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Reg'.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '4':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mutasi_berkas = $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $reg_periksa['no_rawat'])->where('diterima', '<>', '0000-00-00 00:00:00')->oneArray();
                                if($mutasi_berkas){
                                    echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 4,
                                        'waktu' => strtotime($mutasi_berkas['diterima']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                    if ($checkSimpan) {
                                        echo $mutasi_berkas['diterima'].' Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Terima '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$mutasi_berkas){
                                    $mutasi_berkas = $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                                    if($mutasi_berkas){
                                        echo 'B';
                                            date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 4,
                                            'waktu' => strtotime($this->randMinutes($mutasi_berkas['dikirim'],10,15)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    } else if (!$mutasi_berkas){
                                        echo 'C';
                                        $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 4,
                                            'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],15,20)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    }
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '5':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                                if($pemeriksaan_ralan){
                                    echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 5,
                                        'waktu' => strtotime($pemeriksaan_ralan['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$pemeriksaan_ralan) {
                                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                    if ($resep_obat) {
                                        echo 'B ';
                                        # code...
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 5,
                                        'waktu' => strtotime($this->randMinutesMinus($resep_obat['datajam'])) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                    } else {
                                        echo 'C ';
                                        $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 5,
                                            'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],25,30)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    }
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '6':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();

                                if($resep_obat){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 6,
                                        'waktu' => strtotime($resep_obat['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 6)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 6 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '7':
                            $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $reg_periksa['no_rawat'])->where('concat(tgl_perawatan," ",jam)', '<>', 'concat(tgl_peresepan," ",jam_peresepan)')->oneArray();
                                if($resep_obat){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 7,
                                        'waktu' => strtotime($resep_obat['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 7)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 7 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                    $no++;
                }
            }
            if ($task < 8) {
                if ($update == 'true') {
                    header('Refresh: 2; '.$link);
                } else {
                    header('Refresh: 10; '.$link);
                }
            }
        } else {
            if ($update == 'true') {
                if ($task < 8) {
                    $task = $task + 1;
                    $link = url('jknmobile_v2/antrian/updatewaktu2?task='.$task);
                    redirect($link);
                }
            } else {
                if ($task < 8) {
                    $link = url('jknmobile_v2/antrian/updatewaktu2/' . $offsethalaman.'/true?task='.$task);
                    redirect($link);
                }
            }
        }
        exit();
    }
  	
  	public function _getAntreanUpdateWaktu2MJKN($offsethalaman = 0,$update = 'false')
    {   
        $task = 1;
        if(isset($_GET['task']) && $_GET['task'] !='') {
            $task = $_GET['task'];
        }
        $date = date('Y-m-d');
        if(isset($_GET['tgl']) && $_GET['tgl'] !='') {
            $date = $_GET['tgl'];
        }
        $offsethalaman = $offsethalaman + 1;
        $time = date('H:i:s');
        $limit = 10;
        $no = 1;
        $offset = $offsethalaman * $limit;

        if ($update == 'false') {
            $sql = "SELECT * FROM mlite_antrian_referensi WHERE tanggal_periksa = '$date' and status_kirim = 'Sudah' AND keterangan = 'Via MJKN' LIMIT ".$limit." OFFSET ".$offset;
            $stmt = $this->db()->pdo()->prepare($sql);
            $stmt->execute();
            $query = $stmt->fetchAll();
        }
        if ($update == 'true') {
            $query = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa',$date)->where('nomor_referensi','!=','')->where('status','Belum')->where('taskid', $task)->isNull('keterangan')->limit(2)->toArray();
        }

        echo 'Menjalankan WS taskid '.$task.'<br>';
        echo 'Halaman : '.$offsethalaman.'<br>';
        echo 'Tanggal : '.$date.' '.$time.'<br>';
        if ($task == 8) {
            echo 'Selesai Kirim<br>';
        }
        echo '-------------------------------------<br>';
        if ($query) {
            foreach ($query as $q) {
                if ($update == 'true') {
                    $offsethalaman = 1;
                    $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'/true?task='.$task);
                    $mlite_antrian_loket = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('status','Belum')->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)->oneArray();
                    if($mlite_antrian_loket){
                        $data = [
                            'kodebooking' => $q['nomor_referensi'],
                            'taskid' => $task,
                            'waktu' => $q['waktu'],
                        ];
                        $data = json_encode($data);
                        echo 'Request:<br>';
                        echo $data;
    
                        echo '<br>';
                        $url = $this->bpjsurl.'antrean/updatewaktu';
                        $output = BpjsService::post($url, $data, $this->consid, $this->secretkey, $this->user_key, NULL);
                        $json = json_decode($output, true);
                        echo 'Response:<br>';
                        echo json_encode($json);
                        if($json['metadata']['code'] == 200 || $json['metadata']['code'] == 208 || $json == null){
                            $ket = $json['metadata']['message'];
                            if ($json == null) {
                                $ket = 'Ok.';
                            }
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Sudah',
                            'keterangan' => $ket
                        ]);
                        } else {
                            $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['nomor_referensi'])->where('taskid', $task)
                        ->save([
                            'status' => 'Gagal',
                            'keterangan' => $json['metadata']['message']
                        ]);
                        }
                        echo '<br>-------------------------------------<br><br>';
                    }
                }
                if ($update == 'false') {
                    switch ($task) {
                        case '1':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                                if($mlite_antrian_loket){
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['start_time']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $no.'. Berhasil Simpan Task Id '.$task.' No Rekam Medis '.$q['no_rkm_medis'].' dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            }
                            break;
                        case '2':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mlite_antrian_loket = $this->db('mlite_antrian_loket')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('postdate', $date)->oneArray();
                                if($mlite_antrian_loket){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mlite_antrian_loket['postdate'].' '.$mlite_antrian_loket['end_time']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $no.'. Berhasil Simpan Task Id '.$task.'  No Rekam Medis '.$q['no_rkm_medis'].' dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            }
                            break;
                        case '3':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            $count = $this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->count();
                            if($count < 3) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                    $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mutasi_berkas = $this->db('mutasi_berkas')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                                if($mutasi_berkas){
                                        echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($mutasi_berkas['dikirim']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $mutasi_berkas['dikirim'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$mutasi_berkas){
                                    echo 'B '.$q['no_rkm_medis'].' '.$date.' ';
                                    echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'];
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => $task,
                                        'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],5,10)) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , $task)->oneArray();
                                    if ($checkSimpan) {
                                        echo $reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'].' Berhasil Simpan Task Id 3 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Reg'.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '4':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $mutasi_berkas = $this->db('mutasi_berkas')->select('diterima')->where('no_rawat', $reg_periksa['no_rawat'])->where('diterima', '<>', '0000-00-00 00:00:00')->oneArray();
                                if($mutasi_berkas){
                                    echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 4,
                                        'waktu' => strtotime($mutasi_berkas['diterima']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                    if ($checkSimpan) {
                                        echo $mutasi_berkas['diterima'].' Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Terima '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$mutasi_berkas){
                                    $mutasi_berkas = $this->db('mutasi_berkas')->select('dikirim')->where('no_rawat', $reg_periksa['no_rawat'])->where('dikirim', '<>', '0000-00-00 00:00:00')->oneArray();
                                    if($mutasi_berkas){
                                        echo 'B';
                                            date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 4,
                                            'waktu' => strtotime($this->randMinutes($mutasi_berkas['dikirim'],10,15)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu Kirim '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    } else if (!$mutasi_berkas){
                                        echo 'C';
                                        $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 4,
                                            'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],15,20)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 4 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    }
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '5':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->select(['datajam' => 'concat(tgl_perawatan," ",jam_rawat)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                                if($pemeriksaan_ralan){
                                    echo 'A ';
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 5,
                                        'waktu' => strtotime($pemeriksaan_ralan['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else if (!$pemeriksaan_ralan) {
                                    $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();
                                    date_default_timezone_set($this->settings->get('settings.timezone'));
                                    if ($resep_obat) {
                                        echo 'B ';
                                        # code...
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 5,
                                        'waktu' => strtotime($this->randMinutesMinus($resep_obat['datajam'])) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 5)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                    } else {
                                        echo 'C ';
                                        $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                            $this->db('mlite_antrian_referensi_taskid')
                                        ->save([
                                            'tanggal_periksa' => $date,
                                            'nomor_referensi' => $q['kodebooking'],
                                            'taskid' => 5,
                                            'waktu' => strtotime($this->randMinutes($reg_periksa['tgl_registrasi'].' '.$reg_periksa['jam_reg'],25,30)) * 1000,
                                            'status' => 'Belum'
                                        ]);
                                        $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 4)->oneArray();
                                        if ($checkSimpan) {
                                            echo 'Berhasil Simpan Task Id 5 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                        }
                                        echo '<br>-------------------------------------<br><br>';
                                    }
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '6':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_peresepan," ",jam_peresepan)'])->where('no_rawat', $reg_periksa['no_rawat'])->oneArray();

                                if($resep_obat){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 6,
                                        'waktu' => strtotime($resep_obat['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 6)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 6 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        case '7':
                            $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'?task='.$task);
                            if(!$this->db('mlite_antrian_referensi_taskid')->where('tanggal_periksa', $date)->where('nomor_referensi', $q['kodebooking'])->where('taskid', $task)->oneArray()) {
                                $pasien = $this->db('pasien')->where('no_peserta', $q['nomor_kartu'])->oneArray();
                                $q['no_rkm_medis'] = $q['no_rkm_medis'];
                                if($pasien) {
                                $q['no_rkm_medis'] = $pasien['no_rkm_medis'];
                                }
                                $reg_periksa = $this->db('reg_periksa')->where('tgl_registrasi', $date)->where('no_rkm_medis', $q['no_rkm_medis'])->oneArray();
                                $resep_obat = $this->db('resep_obat')->select(['datajam' => 'concat(tgl_perawatan," ",jam)'])->where('no_rawat', $reg_periksa['no_rawat'])->where('concat(tgl_perawatan," ",jam)', '<>', 'concat(tgl_peresepan," ",jam_peresepan)')->oneArray();
                                if($resep_obat){
                                        date_default_timezone_set($this->settings->get('settings.timezone'));
                                        $this->db('mlite_antrian_referensi_taskid')
                                    ->save([
                                        'tanggal_periksa' => $date,
                                        'nomor_referensi' => $q['kodebooking'],
                                        'taskid' => 7,
                                        'waktu' => strtotime($resep_obat['datajam']) * 1000,
                                        'status' => 'Belum'
                                    ]);
                                    $checkSimpan = $this->db('mlite_antrian_referensi_taskid')->where('nomor_referensi', $q['kodebooking'])->where('tanggal_periksa' , $date)->where('taskid' , 7)->oneArray();
                                    if ($checkSimpan) {
                                        echo 'Berhasil Simpan Task Id 7 dengan Kode Booking '.$q['kodebooking'].' dan Waktu '.$checkSimpan['waktu'];
                                    }
                                    echo '<br>-------------------------------------<br><br>';
                                } else {
                                    echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Kosong<br>';
                                }
                            } else {
                                echo $no.'. Task Id '.$task.' : No Rekam Medis '.$q['no_rkm_medis'].' dan Kode Booking '.$q['kodebooking'].' Sudah Ada<br>';
                            }
                            break;
                        default:
                            # code...
                            break;
                    }
                    $no++;
                }
            }
            if ($task < 8) {
                if ($update == 'true') {
                    header('Refresh: 2; '.$link);
                } else {
                    header('Refresh: 10; '.$link);
                }
            }
        } else {
            if ($update == 'true') {
                if ($task < 8) {
                    $task = $task + 1;
                    $link = url('jknmobile_v2/antrian/updatewaktu2mjkn?task='.$task);
                    redirect($link);
                }
            } else {
                if ($task < 8) {
                    $link = url('jknmobile_v2/antrian/updatewaktu2mjkn/' . $offsethalaman.'/true?task='.$task);
                    redirect($link);
                }
            }
        }
        exit();
    }

}
