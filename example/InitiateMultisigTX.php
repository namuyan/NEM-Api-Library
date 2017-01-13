<?php

/* 
 * マルチシグアドレスより出金する為の初期TXを発行します。
 * 動作確認はTestnetでお願いします。
 * This sample program create a initiate multisig transaction sending XEM or Mosaics.
 * Please test on testnet!
 */

    require_once '../NEMApiLibrary.php';
    
    
    // Multisig account info　マルチシグへ変換したアドレス情報。
    $MultisigPubkey = '7e9276ee9de05f28104716e0d512845fbaa3ff80e2a5ed569853d9b64c478781';
    //$MultisigPrikey = '960554450e00a86b8e6ad1ea1d79d781845691554487a868e097fe3ae0372d61';
    //$MultisigAddr = 'TDF2OR-EH4ZYP-IKAPDM-NLWRWQ-PP7JE5-EISF2W-3YFZ';
    
    // Your account info (included cosignatories)　署名者であるあなたのアドレス情報。
    $pubkey = '735c640742022b9adb230870fa877bf7eebf8a03734f0e646fb915074eb4c553';
    $prikey = '8117735f9685fc129dd5f2838125290ce50c6a9d9b1f06f455ec19f0f629e3fd';
    //$Address = 'TAIB57-6OW74E-QWE6H7-SWTNWJ-NGTPN2-Q3YY52-NS3H';
    
    $baseurl = 'http://localhost:7890';
    $net = 'testnet';
    
    
    // create a inner transaction by TransactionBuilder　内TXを生成します。
    $nem = new TransactionBuilder($net);
    $nem->setting($MultisigPubkey, 'its dummy data');  // Don't need privatekey of multisig.　署名はしないのでPrikeyは不要。
    $recipient = 'TBY5I3-X5AW7G-5PBUOV-7OATBG-BZF2MY-LIBSQR-R6GL';  // recieve address. 受け取りアドレス
    $nem->ImportAddr($recipient);
    $nem->message = 'Hi,Im namuyan!';
    $nem->amount = 13;  // 13XEM send
    $nem->EstimateFee();
    $tmp = $nem->SendNEMver1(false);   // When you set FALSE ,created transaction isn't sent to network.　Falseを入れることでNEMﾈｯﾄﾜｰｸへ流れません、Debugもお使い下さい。
    $otherTrans = $tmp['transaction']; // Unsigned transaction is created.　生TXが生成されました。
    /*
     * When you want to send Mosaics ,same way.
     * Difference is SendMosaicver2(false) ,need FALSE.
     * もしMosaicを送るトランザクションを送る場合でも同じ生成方法です。
     * 異なるのはSendMosaicver2(false)にて生TXを取り出すことです。
     */
    
    
    $multi = new Multisig($pubkey, $prikey, $baseurl); // create a outer transaction 外TXを生成
    $multi->set_net($net);
    $reslt = $multi->InitialTX($otherTrans);
    $anal = $multi->analysis($reslt);
    
    
    if($anal['status']){
        // complete
        echo 'Complete !!<BR>',$anal['txid'];
    }else{
        // failed
        echo 'Failed message:',$anal['message'];
    }
    echo '<PRE>';
    print_r($reslt);
    echo '<?PRE>';