<?php

/* 
 * 容易にマルチシグアドレスに変換できます。
 * 動作確認はTestnetでお願いします。
 * You can easily convert multisig address with a few steps.
 * Please test on testnet!
 */

    require_once '../NEMApiLibrary.php';
    
    // Pre Multisig info you want to convert. マルチシグへ変換するアドレス情報。
    $MultisigPubkey = '7e9276ee9de05f28104716e0d512845fbaa3ff80e2a5ed569853d9b64c478781';
    $MultisigPrikey = '960554450e00a86b8e6ad1ea1d79d781845691554487a868e097fe3ae0372d61';
    $MultisigAddr = 'TDF2OR-EH4ZYP-IKAPDM-NLWRWQ-PP7JE5-EISF2W-3YFZ';
    
    // Three cosigner　例として３つの署名者を用意しました。
    $OtherPubKeys = array(
        '735c640742022b9adb230870fa877bf7eebf8a03734f0e646fb915074eb4c553',  // Prikey=8117735f9685fc129dd5f2838125290ce50c6a9d9b1f06f455ec19f0f629e3fd  address=TAIB57-6OW74E-QWE6H7-SWTNWJ-NGTPN2-Q3YY52-NS3H
        'ef3871444a68399e4fb5cb8a7589b7a9feca4048342dbe8a79e8a076709d8830',  // Prikey=66aa5f72972043c92aff189825d9179f485286c216ca72c73e49141f887a8ad6  address=TBY5I3-X5AW7G-5PBUOV-7OATBG-BZF2MY-LIBSQR-R6GL
        '594734ed02c0814dc403ca6253df4dfe84f2f9bd1f3f6fe830140cd5536d588b'   // Prikey=606a3d8323eb88af594c7eda6ae06be85d02d6182301951fbfbf47deb21176de  address=TDGZAR-ZDIXWP-372QOJ-PMFBCO-QL6HBF-QDJK7R-3R5P
    );
    
    
    echo TransactionBuilder::PubKey2Addr($MultisigPubkey)," is same to $MultisigAddr?<BR>";
    foreach ($OtherPubKeys as $OtherPubKeysValue) {
        echo TransactionBuilder::PubKey2Addr($OtherPubKeysValue)," is same to your input?<BR>";
    } // Check your setting
    
    
    // least 2 cosignatories have to sign
    $require = 2;
    
    $baseurl = 'http://localhost:7890';
    $net = 'testnet';
    
    $multi = new Multisig($MultisigPubkey, $MultisigPrikey, $baseurl);
    $multi->set_net($net);
    $reslt = $multi->CreateAddr($OtherPubKeys, $require);
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
