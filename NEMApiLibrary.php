<?php

/* 
 * NEM API Library Ver 1.0
 */

class TransactionBuilder{
    // NEM用トランザクション操作
    public $version_ver1;
    public $version_ver2;
    public $net = 'mainnet';
    public $recipient;
    public $amount; // 小数点アリ
    public $fee; // 小数点アリ
    public $message = '';
    public $payload;
    public $type = 257;
    private $pubkey;
    private $prikey;
    private $baseurl;
    public $mosaic;


    public function __construct($net = 'mainnet') {
        if($net === 'mainnet'){
            $this->version_ver1 = -1744830465;
            $this->version_ver2 = -1744830466;
        }elseif($net === 'testnet'){
            $this->version_ver1 = -1744830463;
            $this->version_ver2 = -1744830462;
        }else{
            throw new Exception("Error:net parameter isn't set ,net is mainnet or testnet.");
        }
        $this->net = $net;
    }
    public function setting($NEMpubkey,$NEMprikey,$baseurl = 'http://localhost:7890'){
        $this->pubkey = $NEMpubkey;
        $this->prikey = $NEMprikey;
        $this->baseurl = $baseurl;
    }
    private function check($amountCheck = true){
        if(empty($this->version_ver1) OR empty($this->version_ver2)){
            throw new Exception("Error:version isn't set.");
        }
        if(empty($this->recipient)){
            throw new Exception("Error:recipient address isn't set.");
        }
        if(!isset($this->amount) AND $amountCheck){
            throw new Exception("Error:amount isn't set.");
        }
        if(!isset($this->fee)){
            throw new Exception("Error:fee isn't set.");
        }
    }
    public function nodelist(){
        if($this->net === 'mainnet'){
            return array('62.75.171.41','104.251.212.131','45.124.65.125','185.53.131.101');
        }else{
            return array('104.128.226.60','23.228.67.85');
        }
    }

    public function InputMosaic($namespace,$name,$amount){
        /* $mosaic内は以下のような配列
        [ makoto.metals.silver:coinを１COIN(１桁)、nem:xemを100XEM(６桁)を送る場合
            {
            "quantity": 10,
            "mosaicId": {
               "namespaceId": "makoto.metals.silver",
               "name": "coin"
            }
        },
        {
            "quantity": 100000000,
            "mosaicId": {
                "namespaceId": "nem",
                "name": "xem"
            }
        }
        ]
         */
        if(SerchMosaicInfo($this->baseurl,$namespace, $name)){
            $mosaic_tmp = array(
                "quantity" => $amount, // 小数点無しの生の値
                "mosaicId" => array(
                    "namespaceId" => $namespace,
                    "name" => $name
                )
            );
            $mosaic = $this->mosaic;
            $mosaic[] = $mosaic_tmp;
            $this->mosaic = $mosaic;
            return TRUE;
        }else{
            // Mosaicの定義が不明
            return FALSE;
        }
    }
    public function SendNEMver1(){
        // NEMを$addressへ送る,Non-mosaic
        // 返り値はTXID、失敗時はFalse
        $url = $this->baseurl ."/transaction/prepare-announce";
        $this->check();
        $POST_DATA = json_encode(
                array('transaction'=> array(
                    'timeStamp'=> (time() - 1427587585), // NEMは1427587585つまり2015/3/29 0:6:25 UTCスタート
                    'amount'   => $this->amount * 1000000,      // NEMは小数点以下6桁まで有効
                    'fee'      => $this->fee    *1000000,
                    'recipient'=> $this->recipient ,
                    'type'     => $this->type ,
                    'deadline' => (time() - 1427587585 + 43200), // 送金の期限
                    'message'=> array(
                        'payload' => isset($this->payload) ? $this->payload : bin2hex($this->message) ,
                        'type'    => 1
                    ),
                    'version'  => $this->version_ver1 ,  // mainnetは-1744830465、testnetは-1744830463
                    'signer'   => $this->pubkey  // signer　サイン主のこと
                    ),
                    'privateKey' => $this->prikey
                ));
        // testnetは-1744830462だと以下のエラーが出る
        // expected value for property mosaics, but none was found これはNEMをモザイクとして送金する必要があるということ？
        //print_r($POST_DATA);print "<BR>"; // debug
        return get_POSTdata($url, $POST_DATA);
        // 返り値　Array ( [innerTransactionHash] => Array ( )
        //                 [code] => 1
        //                 [type] => 1
        //                 [message] => SUCCESS
        //                 [transactionHash] => Array (
        //                                              [data] => 208a41fb815cc0dd6173213a031ba6f956ef60b6530c255a2926e9a8555198e2 ) 
        //                                      )
        // 返り値(error) Array ( [timeStamp] => 55043675
        //                       [error] => Not Found
        //                       [message] => invalid address 'TB235JLAOGALDATDJC7LXDMZSDMFBUMDVIBFVQ' (org.nem.core.model.Address)
        //                       [status] => 404 ) 
    }
    public function SendMosaicVer2(){
        // Mosaic送信用Ver2のトランザクション生成
        // 返り値はTXID、失敗時はFalse
        $mosaic = $this->mosaic;
        $url = $this->baseurl ."/transaction/prepare-announce";
        $this->check(false);
        $POST_DATA = json_encode(
            array(
                'transaction'=>array(
                    'timeStamp' => (time() - 1427587585),
                    'amount'    => 1 * 1000000,    // 実際には１XEM取られない
                    'fee'       => $this->fee * 1000000,
                    'recipient' => $this->recipient ,
                    'type'      => $this->type ,
                    'deadline'  => (time() - 1427587585 + 43200),
                    'message'   => array(
                        'payload'  => isset($this->payload) ? $this->payload : bin2hex($this->message) ,
                        'type'    => 1
                        ),
                    'version'   => $this->version_ver2, // Testnetは-1744830462　,mainnetは-1744830466
                    'signer'    => $this->pubkey,
                    'mosaics'   => $mosaic,
                ),
                'privateKey'=>$this->prikey
            ));
        return get_POSTdata($url, $POST_DATA);
    }
    
    public function EstimateFee(){
        // 送金に必要なFeeを計算し返す
        $mosaic = $this->mosaic;
        if(is_array($mosaic)){
        // With-mosaic
        $fee_tmp = 0;
        foreach ($mosaic as $mosaicValue) {
            $quantity = $mosaicValue['quantity'];
            $namespace = $mosaicValue['mosaicId']['namespaceId'];
            $name = $mosaicValue['mosaicId']['name'];
            $DetailMosaic = SerchMosaicInfo($this->baseurl,$namespace, $name);
            if($DetailMosaic['initialSupply'] <= 10000 AND $DetailMosaic['divisibility'] === 0){
                // SmallBusinessMosaic
                // 分割０でSupply１万以下のMosaicは"SmallBusinessMosaic"と呼ばれFeeが安いぞぃ
                $fee_tmp += 1;
            }else{
                // Others
                // http://mijin.io/forums/forum/日本語/off-topics/717-雑談のお部屋?p=1788#post1788
                // 
                $initialSupplybyUnit = $DetailMosaic['initialSupply'] * pow(10, $DetailMosaic['divisibility']);
                // initialSupply は何故か小数点無しの生の値ではない（謎
                $fee_tmp += round( max(1, min(25, $quantity * 900000 / $initialSupplybyUnit ) - floor(0.8 * log(9000000000000000 / $initialSupplybyUnit ))));
            }
            // 徴収されるNEMやモザイクは含めなくてもよい、NISが勝手に引いてくれる
        } // end of foreach ($mosaic as $mosaicValue) {
        $fee = $fee_tmp;
    }else{
        // Non-mosaic
        $fee_tmp = floor($this->amount / 10000);
        if($fee_tmp < 1){
            $fee = 1;
        }elseif($fee_tmp < 26){
            $fee = $fee_tmp;
        }else{
            $fee = 25;
        }
    }// end of Non-mosaic
    
    $message = isset($this->payload)? $this->payload : $this->message ;
    if(strlen($message) > 0){ // messageのFee
        $fee_tmp = floor(strlen($message) / 32) + 1;
    }else{
        $fee_tmp = 0;
    }
    $fee += $fee_tmp;
    $this->fee = $fee;
    return $fee;
    }
    public function EstimateLevy(){
        // 徴収Mosaic
        $mosaic = $this->mosaic;
        // $return はkeyにnamespace:name,valueに小数点無しの生の値
        foreach ($mosaic as $mosaicValue) {
            $quantity = $mosaicValue['quantity'];
            $namespace = $mosaicValue['mosaicId']['namespaceId'];
            $name = $mosaicValue['mosaicId']['name'];
            $MosaicData = SerchMosaicInfo($this->baseurl,$namespace, $name);
            $levy = $MosaicData['detail']['mosaic']['levy'];
            if(empty($levy)){
                continue;
            }else{
                // 徴収アリ
                if($levy['type'] === 1){
                    // Type1:定額徴収
                    $fee_tmp = $levy['fee'];
                }elseif($levy['type'] === 2){
                    // Type2:%徴収
                    // 100000 * 48 / 10000 = 480
                    // 123456 * 44 / 10000 = 543
                    $fee_tmp = floor($levy['fee'] * $quantity / 10000);
                }
                if(isset($return["{$levy['mosaicId']['namespaceId']}:{$levy['mosaicId']['name']}"])){
                    $return["{$levy['mosaicId']['namespaceId']}:{$levy['mosaicId']['name']}"] += $fee_tmp;
                }else{
                    $return["{$levy['mosaicId']['namespaceId']}:{$levy['mosaicId']['name']}"] = $fee_tmp;
                }
                // 徴収尾張
            }
        }
        
        return $return;
    }
    
    public function ImportAddr($address){
        $tmp = str_replace('-', '', $address);
        $tmp = trim($tmp);
        $this->recipient = $tmp;
    }
    public function analysis($reslt){
        if(isset($reslt['message']) AND $reslt['message'] === 'SUCCESS'){
            return array('status' => TRUE, 'txid' => $reslt['transactionHash']['data']);
        }else{
            return array('status' => FALSE, 'message' => $reslt['message'] );
        }
    }
    public function GetTX($txid){
        // TXIDのみから取引情報を取得可能。
        // ただし36時間以上経過した取引情報について
        // すべてのﾉｰﾄﾞで取得できるわけではない模様。
        // NIS API Doc にはないので注意
        // 設定を変えるとﾛｰｶﾙでも使える→ nis.transactionHashRetentionTime = -1
        $nodelist = $this->nodelist();
        foreach ($nodelist as $nodelistValue) {
            $url = 'http://' .$nodelistValue. ':7890/transaction/get?hash=' .$txid;
            $data = get_json_array($url);
            if($data){
                return $data;
            }else{
                continue; 
            }
        }
        return FALSE;
    }
}

class Apostille {
    // アポスティーユ作成
    public $payload ;
    public $algo ;
    public $type ;
    public $net;
    public $filename ;
    public $recipient;
    public $txid;


    public function __construct($filename = NULL) {
        if(isset($filename)){
        $this->setting($filename);
        }
    }
    public function set_net($net){
        if(!in_array($net, array('mainnet','testnet'),true)){
            throw new Exception ("Error:parameter of net ,mainnet or testnet are allowd.");
        }
        $type = $this->type;
        $this->net = $net;
        if($net === 'mainnet'){
            //$this->version = -1744830465;
            $this->recipient = ($type === 'public')?'NCZSJHLTIMESERVBVKOW6US64YDZG2PFGQCSV23J':'NAX4LLSZ7N3JHWQYQSAMGABTD5SVHFEJD2BTWQBN';
        }else{
            //$this->version = -1744830463;
            $this->recipient = ($type === 'public')?'TC7MCY5AGJQXZQ4BN3BOPNXUVIGDJCOHBPGUM2GE':'TDXJZ42QNFCGEZVCZFZSE2QPKQU7MDZ4SNO6NOI4';
        }
    }

    public function setting($filename,$type = 'public',$algo = 'sha256',$net = 'mainnet'){
        if(!preg_match('/^(.+?)(\.[^.]+?)$/', $filename)){
            throw new Exception("Error:$filename ファイルに拡張子を加えて下さい。");
        }
        $this->filename = $filename;
        $this->type = $type;
        $this->algo = $algo;
        if(!file_exists($filename)){
            throw new Exception ("Error:$filename isn't exist.");
        }
        if(!in_array($type, array('public','private'),true)){
            throw new Exception ("Error:parameter of type ,public or private are allowd.");
        }
        if(!in_array($algo, array('md5','sha1','sha256','sha3-256','sha3-512'),true)){
            throw new Exception ("Error:parameter of algo ,md5 ,sha1 ,sha256 or keccak(SHA3) are allowd.");
        }
        $this->set_net($net);
    }
    public function Run(){
        /* https://github.com/strawbrary/php-sha3
         * SHA3のハッシュはこのモジュールを導入
         * 下部にSHA3のハッシュ化classを置いてあるがかなり遅い
         * 参考メモ
         * PATHを通していない場合→/opt/lampp/bin/phpize
         * ./configure --enable-sha3 --with-php-config=/opt/lampp/bin/php-config
         * cp /modules/sha3.so /opt/lampp/lib/php/extensions/no-debug-non-zts-20121212/
         */
        $hex = 'fe'; // 形式 HEX
        switch ($this->algo) {
            case 'md5' : $algo = 1; break;
            case 'sha1' : $algo = 2; break;
            case 'sha256' : $algo = 3; break;
            case 'sha3-256' : $algo = 8; break;
            case 'sha3-512' : $algo = 9; break;
            default:throw new Exception ("Error:未対応の暗号方式です。");
        }
        if($algo < 4){
            $hash = hash_file($this->algo, $this->filename);
        }elseif($algo === 8){
            $all = file_get_contents($this->filename);
            //$hash = sha3($all,256);
            set_time_limit(250);
            $hash = Sha3_0xbb::hash($all, 256 );
            set_time_limit(30);
        }elseif($algo === 9){
            $all = file_get_contents($this->filename);
            //$hash = sha3($all,512);
            set_time_limit(250);
            $hash = Sha3_0xbb::hash($all, 512 );
            set_time_limit(30);
        }else{
            die("Error:$algo が例外です。");
        }
        if($this->type === 'public'){
            $this->payload = $hex .'4e54590'. $algo . $hash;
        }else{
            // 暗号化の仕方がわからない
            throw new Exception ("Error:暗号化に未対応.");
            $this->payload = $hex .'4e54598'. $algo . $hash;
        }
    }
    public function send($NEMpubkey,$NEMprikey,$baseurl = 'http://localhost:7890') {
        $nem = new TransactionBuilder($this->net);
        $nem -> setting($NEMpubkey, $NEMprikey, $baseurl);
        $nem->payload = $this->payload;
        $nem -> amount = 0;
        $nem -> recipient = $this->recipient;
        $nem ->EstimateFee();
        $tmp = $nem ->SendNEMver1();
        $reslt = $nem->analysis($tmp);
        $reslt['fee'] = $nem->fee;
        if($reslt['status']){
            $this->txid = $reslt['txid'];
        }
        return $reslt;
    }
    public function Outfile($dir = '/opt/lampp/htdocs/apo/'){
        // あらかじめ保存場所$dirを設定
        $txid = $this->txid;
        $date = date("Y-m-d");
        preg_match('/.*?([^\/]+?)(\.[^.]+?)$/', $this->filename, $matches);
        $dest = $dir . $matches[1] ." -- Apostille TX $txid -- Date $date" .$matches[2];
        return copy($this->filename, $dest);
    }

    public function Check($baseurl){
        $filename_original = $this->filename;
        $pattern = '/^(.*?\/)([^\/]+?)\s\-\-\sApostille\sTX\s([0-9abcdefABCDEF]+?)\s\-\-\sDate\s([0-9\-]+?)(\.[^.]+?)$/';
        if(!preg_match($pattern, $filename_original, $matches)){
            throw new Exception("Error:FilenameがApostilleで使われる形式ではありません。");
        }
        $dirpass = $matches[1];
        $filename = $matches[2];
        $txid = $matches[3];
        $date = $matches[4];
        $ex = $matches[5];
        
        $nem = new TransactionBuilder($this->net);
        $txdata = $nem->GetTX($txid);
        if(!$txdata){
            // 登録されていないか全ノード死亡
            return array('status' => FALSE ,'code' => 1);
        }
        if(preg_match('/^fe4e5459([08])([0-9abcdef])([0-9abcdef]*)/', $txdata['transaction']['message']['payload'], $matches)){
            $type = $matches[1];
            $hash = $matches[3];
            switch ($matches[2]) {
                case 1:$this->algo = 'md5';break;
                case 2:$this->algo = 'sha1';break;
                case 3:$this->algo = 'sha256';break;
                case 8:$this->algo = 'sha3-256';break;
                case 9:$this->algo = 'sha3-512';break;
                default:return array('status' => FALSE ,'code' => 3);
            }
        }else{
            // messageが正規でない
            return array('status' => FALSE ,'code' => 2);
        }
        $this->Run();
        if($this->payload === $txdata['transaction']['message']['payload']){
            return array('status' => true ,'code' => 0,'detail' => $txdata['transaction']);
        }
    }
    public function analysis($baseurl,$reslt){
        $transaction = $reslt['detail'];
        $timeStamp = $transaction['timeStamp'] + 1427587585;
        $url = $baseurl.'/account/get/from-public-key?publicKey='. $transaction['signer'];
        $tmp = get_json_array($url);
        $creator = $tmp['account']['address'];
        return array('timeStamp' => $timeStamp ,'creator' => $creator);
    }
}

class common{
   public static function getGET($name){
        $ret = filter_input(INPUT_GET, $name);
        if (isset($ret)){
            $ret =str_replace("\0", "", $ret);//Nullバイト攻撃対策
            return htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
        return '';
    }
    public static function getPost($name){
        $ret = filter_input(INPUT_POST, $name);
        if (isset($ret)){
            $ret =str_replace("\0", "", $ret);//Nullバイト攻撃対策
            return htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
         return '';
    }
    public static function getCookie($name){
        $ret = filter_input(INPUT_COOKIE, $name);
        if (isset($ret)){
            $ret =str_replace("\0", "", $ret);//Nullバイト攻撃対策
            return htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
         return '';
    }
    public static function getRequest($name){
        $ret = filter_input(INPUT_REQUEST, $name);
        if (isset($ret)){
            $ret =str_replace("\0", "", $ret);//Nullバイト攻撃対策
            return htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
         return '';
    }
    public static function FileDisass($file){
        // filepassを分解、/pass/to/file.png を
        // 1=/pass/to/ ,2=file ,3=.png 
        if(preg_match('/^(.*?)([^\/]+?)(\.[^.]+?)$/', $file, $matches)){
            return $matches;
        }else{
            return false;
        }
    }
}




function get_json_array($url){
    /* JSONを簡単にゲットできるモジュール
     * 返り値は配列化
     */
    //$url = "https://c-cex.com/t/dash-btc.json"; //debug
    //$json = file_get_contents($url);
    //return json_decode(mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN'),true);
    
$options = [
    CURLOPT_URL => $url,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_SSL_VERIFYPEER => false,
];
    $ch = curl_init();
     curl_setopt_array($ch, $options);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $json = curl_exec($ch);
    // ステータスコード取得
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     curl_close($ch);

if($code !== 200){
    return false;
}  else {
    $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
    $arr = json_decode($json,true);
    return $arr;
}
     
} // end of get_json_array

function get_POSTdata($url,$POST_DATA = null){
    //$POST_DATAにPOSTデータ、key=>valueの配列型
    $curl=curl_init($url);
        curl_setopt($curl,CURLOPT_POST, TRUE);
        if(is_array($POST_DATA)){
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
        }else{
            // jsonを送信時
            curl_setopt($curl, CURLOPT_POSTFIELDS, $POST_DATA);
            curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type: application/json"));
        }
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl,CURLOPT_COOKIEJAR,      'cookie');
        curl_setopt($curl,CURLOPT_COOKIEFILE,     'tmp');
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt( $curl , CURLOPT_TIMEOUT , 20 ) ;
        if(! is_array($POST_DATA)){
            // jsonを送信時
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_AUTOREFERER => true,
	);
        curl_setopt_array($curl, $options);
        }
    return json_decode(curl_exec($curl),true);
}// end of get_POSTdata

function RandumStr($num){
    $base58 = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    $str = '';
    for($i=0;$i<$num;$i++){
        $str .= substr($base58, mt_rand(0, 57), 1);
    }
    return $str;
}

function SerchMosaicInfo($baseurl,$namespace,$name){
    // Mosaicの詳細を検索、検索して無かったらFalse返す
    if($namespace !== 'nem' AND $name !== 'xem'){
        $url = $baseurl."/namespace/mosaic/definition/page?namespace=$namespace";
        $DetailMosaic = get_json_array($url);
        if(!isset($DetailMosaic)){return FALSE;}
        foreach ($DetailMosaic['data'] as $DetailMosaicValue) {
            if($DetailMosaicValue['mosaic']['id']['name'] === $name){
                foreach ($DetailMosaicValue['mosaic']['properties'] as $DetailMosaicValue2) {
                    if($DetailMosaicValue2['name'] === 'divisibility'){
                        $divisibility = (int)$DetailMosaicValue2['value']; // ０～６
                    }elseif($DetailMosaicValue2['name'] === 'initialSupply'){
                        $initialSupply = (int)$DetailMosaicValue2['value']; // 最小単位でないから注意
                    }elseif($DetailMosaicValue2['name'] === 'supplyMutable'){
                        $supplyMutable = (boolean)$DetailMosaicValue2['value'];
                    }elseif($DetailMosaicValue2['name'] === 'transferable'){
                        $transferable = (boolean)$DetailMosaicValue2['value'];
                    }
                }
                unset( $DetailMosaicValue['mosaic']['properties'] );
                $detail = $DetailMosaicValue;
                break;
            }
        }
        if(!isset($divisibility)){return FALSE;}
    }elseif($namespace === 'nem' AND $name === 'xem'){
        $divisibility = 6;  // 小数点以下６桁まで可能
        $initialSupply = 8999999999;
        $supplyMutable = false;  // trueだと追加発行可能
        $transferable = true;  // trueだと譲渡可能
        $detail = array(
            'meta' => array( 'id' => 1),
            'mosaic' => array(
                'creator' => '',
                'description' => 'Its dummy data',
                'id' => array(
                    'namespaceId' => '',
                    'name' => ''
                ),
                'levy' => array()
            )
        );
    }else{
        return false;
    }
    return array('divisibility'=>$divisibility,
                'initialSupply'=>$initialSupply,
                'supplyMutable'=>$supplyMutable,
                'transferable'=>$transferable,
                'detail'=>$detail);
    /*返り値
{
  "divisibility": 2,
  "initialSupply": 10000,
  "supplyMutable": true,
  "transferable": true,
  "detail": {
    "meta": {
      "id": 191
    },
    "mosaic": {
      "creator": "47900452f5843f391e6485c5172b0e1332bf0032b04c4abe23822754214caec3",
      "description": "もってるといいことがある....はず、FaucetのDonationのお返しに送金されるよ",
      "id": {
        "namespaceId": "namuyan",
        "name": "namu"
      },
      "levy": {}
    }
  }
}
     */
}




/* Pure PHP implementation of SHA-3
 * https://github.com/0xbb/php-sha3
 * MIT
 * PHP7などであるならばSHA3_DesktopdのPurePHP使用可能
 * 何故か自分の環境では3倍遅かった
 */
final class Sha3_0xbb {
    const KECCAK_ROUNDS = 24;
    private static $keccakf_rotc = [1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 2, 14, 27, 41, 56, 8, 25, 43, 62, 18, 39, 61, 20, 44];
    private static $keccakf_piln = [10, 7, 11, 17, 18, 3, 5, 16, 8, 21, 24, 4, 15, 23, 19, 13, 12,2, 20, 14, 22, 9, 6, 1];
    private static function keccakf64(&$st, $rounds)
    {
        $keccakf_rndc = [
            [0x00000000, 0x00000001], [0x00000000, 0x00008082], [0x80000000, 0x0000808a], [0x80000000, 0x80008000],
            [0x00000000, 0x0000808b], [0x00000000, 0x80000001], [0x80000000, 0x80008081], [0x80000000, 0x00008009],
            [0x00000000, 0x0000008a], [0x00000000, 0x00000088], [0x00000000, 0x80008009], [0x00000000, 0x8000000a],
            [0x00000000, 0x8000808b], [0x80000000, 0x0000008b], [0x80000000, 0x00008089], [0x80000000, 0x00008003],
            [0x80000000, 0x00008002], [0x80000000, 0x00000080], [0x00000000, 0x0000800a], [0x80000000, 0x8000000a],
            [0x80000000, 0x80008081], [0x80000000, 0x00008080], [0x00000000, 0x80000001], [0x80000000, 0x80008008]
        ];
        $bc = [];
        for ($round = 0; $round < $rounds; $round++) {
            // Theta
            for ($i = 0; $i < 5; $i++) {
                $bc[$i] = [
                    $st[$i][0] ^ $st[$i + 5][0] ^ $st[$i + 10][0] ^ $st[$i + 15][0] ^ $st[$i + 20][0],
                    $st[$i][1] ^ $st[$i + 5][1] ^ $st[$i + 10][1] ^ $st[$i + 15][1] ^ $st[$i + 20][1]
                ];
            }
            for ($i = 0; $i < 5; $i++) {
                $t = [
                    $bc[($i + 4) % 5][0] ^ (($bc[($i + 1) % 5][0] << 1) | ($bc[($i + 1) % 5][1] >> 31)) & (0xFFFFFFFF),
                    $bc[($i + 4) % 5][1] ^ (($bc[($i + 1) % 5][1] << 1) | ($bc[($i + 1) % 5][0] >> 31)) & (0xFFFFFFFF)
                ];
                for ($j = 0; $j < 25; $j += 5) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ $t[0],
                        $st[$j + $i][1] ^ $t[1]
                    ];
                }
            }
            // Rho Pi
            $t = $st[1];
            for ($i = 0; $i < 24; $i++) {
                $j = self::$keccakf_piln[$i];
                $bc[0] = $st[$j];
                $n = self::$keccakf_rotc[$i];
                $hi = $t[0];
                $lo = $t[1];
                if ($n >= 32) {
                    $n -= 32;
                    $hi = $t[1];
                    $lo = $t[0];
                }
                $st[$j] =[
                    (($hi << $n) | ($lo >> (32 - $n))) & (0xFFFFFFFF),
                    (($lo << $n) | ($hi >> (32 - $n))) & (0xFFFFFFFF)
                ];
                $t = $bc[0];
            }
            //  Chi
            for ($j = 0; $j < 25; $j += 5) {
                for ($i = 0; $i < 5; $i++) {
                    $bc[$i] = $st[$j + $i];
                }
                for ($i = 0; $i < 5; $i++) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ ~$bc[($i + 1) % 5][0] & $bc[($i + 2) % 5][0],
                        $st[$j + $i][1] ^ ~$bc[($i + 1) % 5][1] & $bc[($i + 2) % 5][1]
                    ];
                }
            }
            // Iota
            $st[0] = [
                $st[0][0] ^ $keccakf_rndc[$round][0],
                $st[0][1] ^ $keccakf_rndc[$round][1]
            ];
        }
    }
    private static function keccak64($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        $capacity /= 8;
        $inlen = self::ourStrlen($in_raw);
        $rsiz = 200 - 2 * $capacity;
        $rsizw = $rsiz / 8;
        $st = [];
        for ($i = 0; $i < 25; $i++) {
            $st[] = [0, 0];
        }
        for ($in_t = 0; $inlen >= $rsiz; $inlen -= $rsiz, $in_t += $rsiz) {
            for ($i = 0; $i < $rsizw; $i++) {
                $t = unpack('V*', self::ourSubstr($in_raw, $i * 8 + $in_t, 8));
                $st[$i] = [
                    $st[$i][0] ^ $t[2],
                    $st[$i][1] ^ $t[1]
                ];
            }
            self::keccakf64($st, self::KECCAK_ROUNDS);
        }
        $temp = self::ourSubstr($in_raw, $in_t, $inlen);
        $temp = str_pad($temp, $rsiz, "\x0", STR_PAD_RIGHT);
        $temp[$inlen] = chr($suffix);
        $temp[$rsiz - 1] = chr($temp[$rsiz - 1] | 0x80);
        for ($i = 0; $i < $rsizw; $i++) {
            $t = unpack('V*', self::ourSubstr($temp, $i * 8, 8));
            $st[$i] = [
                $st[$i][0] ^ $t[2],
                $st[$i][1] ^ $t[1]
            ];
        }
        self::keccakf64($st, self::KECCAK_ROUNDS);
        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= $t = pack('V*', $st[$i][1], $st[$i][0]);
        }
        $r = self::ourSubstr($out, 0, $outputlength / 8);
        return $raw_output ? $r : bin2hex($r);
    }
    private static function keccakf32(&$st, $rounds)
    {
        $keccakf_rndc = [
            [0x0000, 0x0000, 0x0000, 0x0001], [0x0000, 0x0000, 0x0000, 0x8082], [0x8000, 0x0000, 0x0000, 0x0808a], [0x8000, 0x0000, 0x8000, 0x8000],
            [0x0000, 0x0000, 0x0000, 0x808b], [0x0000, 0x0000, 0x8000, 0x0001], [0x8000, 0x0000, 0x8000, 0x08081], [0x8000, 0x0000, 0x0000, 0x8009],
            [0x0000, 0x0000, 0x0000, 0x008a], [0x0000, 0x0000, 0x0000, 0x0088], [0x0000, 0x0000, 0x8000, 0x08009], [0x0000, 0x0000, 0x8000, 0x000a],
            [0x0000, 0x0000, 0x8000, 0x808b], [0x8000, 0x0000, 0x0000, 0x008b], [0x8000, 0x0000, 0x0000, 0x08089], [0x8000, 0x0000, 0x0000, 0x8003],
            [0x8000, 0x0000, 0x0000, 0x8002], [0x8000, 0x0000, 0x0000, 0x0080], [0x0000, 0x0000, 0x0000, 0x0800a], [0x8000, 0x0000, 0x8000, 0x000a],
            [0x8000, 0x0000, 0x8000, 0x8081], [0x8000, 0x0000, 0x0000, 0x8080], [0x0000, 0x0000, 0x8000, 0x00001], [0x8000, 0x0000, 0x8000, 0x8008]
        ];
        $bc = [];
        for ($round = 0; $round < $rounds; $round++) {
            // Theta
            for ($i = 0; $i < 5; $i++) {
                $bc[$i] = [
                    $st[$i][0] ^ $st[$i + 5][0] ^ $st[$i + 10][0] ^ $st[$i + 15][0] ^ $st[$i + 20][0],
                    $st[$i][1] ^ $st[$i + 5][1] ^ $st[$i + 10][1] ^ $st[$i + 15][1] ^ $st[$i + 20][1],
                    $st[$i][2] ^ $st[$i + 5][2] ^ $st[$i + 10][2] ^ $st[$i + 15][2] ^ $st[$i + 20][2],
                    $st[$i][3] ^ $st[$i + 5][3] ^ $st[$i + 10][3] ^ $st[$i + 15][3] ^ $st[$i + 20][3]
                ];
            }
            for ($i = 0; $i < 5; $i++) {
                $t = [
                    $bc[($i + 4) % 5][0] ^ ((($bc[($i + 1) % 5][0] << 1) | ($bc[($i + 1) % 5][1] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][1] ^ ((($bc[($i + 1) % 5][1] << 1) | ($bc[($i + 1) % 5][2] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][2] ^ ((($bc[($i + 1) % 5][2] << 1) | ($bc[($i + 1) % 5][3] >> 15)) & (0xFFFF)),
                    $bc[($i + 4) % 5][3] ^ ((($bc[($i + 1) % 5][3] << 1) | ($bc[($i + 1) % 5][0] >> 15)) & (0xFFFF))
                ];
                for ($j = 0; $j < 25; $j += 5) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ $t[0],
                        $st[$j + $i][1] ^ $t[1],
                        $st[$j + $i][2] ^ $t[2],
                        $st[$j + $i][3] ^ $t[3]
                    ];
                }
            }
            // Rho Pi
            $t = $st[1];
            for ($i = 0; $i < 24; $i++) {
                $j = self::$keccakf_piln[$i];
                $bc[0] = $st[$j];
                $n = self::$keccakf_rotc[$i] >> 4;
                $m = self::$keccakf_rotc[$i] % 16;
                $st[$j] =  [
                    ((($t[(0+$n) %4] << $m) | ($t[(1+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(1+$n) %4] << $m) | ($t[(2+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(2+$n) %4] << $m) | ($t[(3+$n) %4] >> (16-$m))) & (0xFFFF)),
                    ((($t[(3+$n) %4] << $m) | ($t[(0+$n) %4] >> (16-$m))) & (0xFFFF))
                ];
                $t = $bc[0];
            }
            //  Chi
            for ($j = 0; $j < 25; $j += 5) {
                for ($i = 0; $i < 5; $i++) {
                    $bc[$i] = $st[$j + $i];
                }
                for ($i = 0; $i < 5; $i++) {
                    $st[$j + $i] = [
                        $st[$j + $i][0] ^ ~$bc[($i + 1) % 5][0] & $bc[($i + 2) % 5][0],
                        $st[$j + $i][1] ^ ~$bc[($i + 1) % 5][1] & $bc[($i + 2) % 5][1],
                        $st[$j + $i][2] ^ ~$bc[($i + 1) % 5][2] & $bc[($i + 2) % 5][2],
                        $st[$j + $i][3] ^ ~$bc[($i + 1) % 5][3] & $bc[($i + 2) % 5][3]
                    ];
                }
            }
            // Iota
            $st[0] = [
                $st[0][0] ^ $keccakf_rndc[$round][0],
                $st[0][1] ^ $keccakf_rndc[$round][1],
                $st[0][2] ^ $keccakf_rndc[$round][2],
                $st[0][3] ^ $keccakf_rndc[$round][3]
            ];
        }
    }
    private static function keccak32($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        $capacity /= 8;
        $inlen = self::ourStrlen($in_raw);
        $rsiz = 200 - 2 * $capacity;
        $rsizw = $rsiz / 8;
        $st = [];
        for ($i = 0; $i < 25; $i++) {
            $st[] = [0, 0, 0, 0];
        }
        for ($in_t = 0; $inlen >= $rsiz; $inlen -= $rsiz, $in_t += $rsiz) {
            for ($i = 0; $i < $rsizw; $i++) {
                $t = unpack('v*', self::ourSubstr($in_raw, $i * 8 + $in_t, 8));
                $st[$i] = [
                    $st[$i][0] ^ $t[4],
                    $st[$i][1] ^ $t[3],
                    $st[$i][2] ^ $t[2],
                    $st[$i][3] ^ $t[1]
                ];
            }
            self::keccakf32($st, self::KECCAK_ROUNDS);
        }
        $temp = self::ourSubstr($in_raw, $in_t, $inlen);
        $temp = str_pad($temp, $rsiz, "\x0", STR_PAD_RIGHT);
        $temp[$inlen] = chr($suffix);
        $temp[$rsiz - 1] = chr($temp[$rsiz - 1] | 0x80);
        for ($i = 0; $i < $rsizw; $i++) {
            $t = unpack('v*', self::ourSubstr($temp, $i * 8, 8));
            $st[$i] = [
                $st[$i][0] ^ $t[4],
                $st[$i][1] ^ $t[3],
                $st[$i][2] ^ $t[2],
                $st[$i][3] ^ $t[1]
            ];
        }
        self::keccakf32($st, self::KECCAK_ROUNDS);
        $out = '';
        for ($i = 0; $i < 25; $i++) {
            $out .= $t = pack('v*', $st[$i][3],$st[$i][2], $st[$i][1], $st[$i][0]);
        }
        $r = self::ourSubstr($out, 0, $outputlength / 8);
        return $raw_output ? $r: bin2hex($r);
    }
    // 0 = not run, 1 = 64 bit passed, 2 = 32 bit passed, 3 = failed
    private static $test_state = 0;
    private static function selfTest()
    {
        if(self::$test_state === 1 || self::$test_state === 2){
            return;
        }
        if(self::$test_state === 3){
            throw new \Exception('Sha3 previous self test failed!');
        }
        $in = '';
        //$md = '6b4e03423667dbb73b6e15454f0eb1abd4597f9a1b078e3f5b5a6bc7'; // 0x06
        $md = 'f71837502ba8e10837bdd8d365adb85591895602fc552b48b7390abd'; // 0x01
        if(self::keccak64($in, 224, 224, 0x01, false) === $md){
            self::$test_state = 1;
            return;
        }
        if(self::keccak32($in, 224, 224, 0x01, false) === $md){
            self::$test_state = 2;
            return;
        }
        self::$test_state = 3;
        throw new \Exception('Sha3 self test failed!');
    }
    private static function keccak($in_raw, $capacity, $outputlength, $suffix, $raw_output)
    {
        self::selfTest();
        if(self::$test_state === 1) {
            return self::keccak64($in_raw, $capacity, $outputlength, $suffix, $raw_output);
        }
        return self::keccak32($in_raw, $capacity, $outputlength, $suffix, $raw_output);
    }
    public static function hash($in, $mdlen, $raw_output = false)
    {
        if( ! in_array($mdlen, [224, 256, 384, 512], true)) {
            throw new \Exception('Unsupported Sha3 Hash output size.');
        }
        return self::keccak($in, $mdlen, $mdlen, 0x01, $raw_output);
    }
    public static function shake($in, $security_level, $outlen, $raw_output = false)
    {
        if( ! in_array($security_level, [128, 256], true)) {
            throw new \Exception('Unsupported Sha3 Shake security level.');
        }
        return self::keccak($in, $security_level, $outlen, 0x1f, $raw_output);
    }
    /**
     *  Multi-byte-safe string functions borrowed from https://github.com/sarciszewski/php-future
     */
    /**
     * Multi-byte-safe string length calculation
     *
     * @param string $str
     * @return int
     */
    private static function ourStrlen($str)
    {
        // Premature optimization: cache the function_exists() result
        static $exists = null;
        if ($exists === null) {
            $exists = \function_exists('\\mb_strlen');
        }
        // If it exists, we need to make sure we're using 8bit mode
        if ($exists) {
            $length =  \mb_strlen($str, '8bit');
            if ($length === false) {
                throw new \Exception('mb_strlen() failed.');
            }
            return $length;
        }
        return \strlen($str);
    }
    /**
     * Multi-byte-safe substring calculation
     *
     * @param string $str
     * @param int $start
     * @param int $length (optional)
     * @return string
     */
    private static function ourSubstr($str, $start = 0, $length = null)
    {
        // Premature optimization: cache the function_exists() result
        static $exists = null;
        if ($exists === null) {
            $exists = \function_exists('\\mb_substr');
        }
        // If it exists, we need to make sure we're using 8bit mode
        if ($exists) {
            return \mb_substr($str, $start, $length, '8bit');
        } elseif ($length !== null) {
            return \substr($str, $start, $length);
        }
        return \substr($str, $start);
    }
}