<?php

/* 
 * このプログラムでは複数のMosaicを送ることができます。
 * 動作確認はTestnetでお願いします。
 * This sample program is transfer same mosaics.
 * Please test on testnet!
 */

    require_once '../NEMApiLibrary.php';
    
    $net = 'testnet';
    $NEMpubkey = '';
    $NEMprikey = '';
    $baseurl = 'http://localhost:7890';
    $address = 'TDEK3DOKN54XWEVUNXJOLWDJMYEF2G7HPK2LRU5W'; // 送り先 recipient

    
    $mosaic = new TransactionBuilder($net);
    $mosaic->setting($NEMpubkey, $NEMprikey, $baseurl);
    $mosaic->ImportAddr($address);
    $mosaic->message = 'We are the one.';
    /*
     * もし、namuyan:namu を 23.45 (divisibility = 2)
     *       godtanu:godtanu を 100 (divisibility = 0)
     * 送るとしたら、
     * if Mosaics ( 23.45 namuyan:namu AND 100 godtanu:godtanu ) transfer,
     */
    $mosaic->InputMosaic('namuyan', 'namu', 2345);
    $mosaic->InputMosaic('godtanu', 'godtanu', 100);
    
    $fee = $mosaic->EstimateFee();
    $levy = $mosaic->EstimateLevy();
    $reslt = $mosaic->SendMosaicVer2();
    $anal = $mosaic->analysis($reslt);
    
    echo '<P>','Fee is ',$fee,'<BR>';
    if($anal['status']){
        echo 'TXID is ',$anal['txid'],'</P>';
        echo '<PRE>',"levy is\n";
        print_r($levy);
        echo "Send mosaic is or are\n";
        print_r($mosaic->mosaic);
        echo '<?PRE>';
    }else{
        echo "Fail to send.<BR>error message: {$anal['message']}</P>";
    }