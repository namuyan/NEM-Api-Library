<?php

/* 
 * NEMを送金するサンプルプログラムです。
 * 動作確認はTestnetでお願いします。
 * This sample program is transfer XEM.
 * Please test on testnet!
 */


    require_once '../NEMApiLibrary.php';
    
    
    $net = 'testnet';
    $NEMpubkey = '';
    $NEMprikey = '';
    $baseurl = 'http://localhost:7890';
    $address = 'TDEK3DOKN54XWEVUNXJOLWDJMYEF2G7HPK2LRU5W'; // 送り先 recipient
    
    
    $nem = new TransactionBuilder($net);
    $nem->setting($NEMpubkey, $NEMprikey, $baseurl);
    $nem->ImportAddr($address);
    $nem->amount = 12; // 12XEM
    $nem->message = 'I love nem.';
  //$nem->payload = 'fe123456789abcdef'; //変換されずにHEXのまま送られます. If you want to send raw hex code ,use $nem->payload
    $fee = $nem->EstimateFee();
    $reslt = $nem->SendNEMver1();
    $anal = $nem->analysis($reslt);
    
    echo '<P>','Fee is ',$fee,'<BR>';
    if($anal['status']){
        echo 'TXID is ',$anal['txid'],'</P>';
    }else{
        echo "Fail to send.<BR>error message: {$anal['message']}</P>";
    }
    
    
    
