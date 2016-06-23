<?php
use \Fuel\Core\Config;
use \Fuel\Core\DB;
use Orm\Model;

abstract class Kontrol
{
  public static $port = 80;
  public static $isim = 'HTTP';
  public static $timeout = 1;
  public static $ozel = false;
  public static $detayvar = false;

  public static function portver($bilgi=array()){
    $port = self::$port;
    if ($bilgi['port']) {
      $port = $bilgi['port'];
    } else if ($bilgi['servis_port']) {
      $port = $bilgi['servis_port'];
    }
    return $port;
  }

  public static function detay($did){
    $hng = '\Model_Ko'.strtolower(self::$isim);
    $ekdat = $hng::query()->where('port_id', $did)->get_one();
    return $ekdat;
  }

  public static function derle($data)
  {
    $out = array(
        'id'=>$data->id,
        'host'=>$data->sunucu->ip,
        'port'=>$data->port,
        'ozel'=>$data->ozel,
        'nasil'=>$data->nasil,
        'timeout'=>$data->timeout,
        'servis'=>$data->service->adi,
        'servis_port'=>$data->service->port
      );
    return $out;
  }

  public static function check ($data)
  {
    $data['port'] = self::portver($data);
    $data['timeout'] = isset($data['timeout'])?$data['timeout']:self::$timeout;
    if ($data['ozel']) {
      self::$ozel = true;
    }
    if (static::$detayvar) {
      $data['ekdata'] = self::detay($data['id']);
    }
    return $data;
  }



  public static function basit ($host, $port=0, $timeout = 1)
  {
    $ts = new \Zaman();
    $port = self::portver(array('port'=>$port));
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($connection) {
        $result = $ts->sonuc();
        fclose($connection);
    } else {
        $result = 0;
    }
    return $result;
  }

  public static function run()
  {
    $yer_id = \Model_Service::query()->where('adi', static::$isim)->get_one()->id;
    $hosts = \Model_Port::find('all', array(
          'related' => array('sunucu','grup','service'),
          'where' => array(
              array("service_id",$yer_id),
              array("aktif",1)
            )
        )
    );

    foreach ($hosts as $pop) {
      $data = self::check(self::derle($pop));
      $sonuc = static::basla($data);
      $kayit = new \Model_Dinleme();
      $kayit->port_id = $pop->id;
      $kayit->user_id = $pop->user_id;
      $kayit->yer_id = 1;
      $kayit->sure = $sonuc;
      $kayit->durum = $sonuc > 0 ? 1 : 0;
      $kayit->save();
    }
    return '';
  }

}
