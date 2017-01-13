<?php

/* 
 * 署名者としてネットワークに流れてきたマルチシグトランサクションに署名します。
 * 動作確認はTestnetでお願いします。
 * Sign a multisig as a cosignatory.
 * Please test on testnet!
 */

    require_once '../NEMApiLibrary.php';
    
    $baseurl = 'http://localhost:7890';
    $net = 'testnet';
    
    // Your account info (included cosignatories)　署名者であるあなたのアドレス情報。
    $pubkey = '594734ed02c0814dc403ca6253df4dfe84f2f9bd1f3f6fe830140cd5536d588b';
    $prikey = '606a3d8323eb88af594c7eda6ae06be85d02d6182301951fbfbf47deb21176de';
    
    // Check you unconfirmed transaction exist　未署名のTXが存在するか確認します。
    $reslt = Multisig::checkMultisigTX($pubkey);
    
    if(isset($reslt)){
        $innerTransactionHash = $reslt[0]['innerTransactionHash'];
        $MultisigPubkey = $reslt[0]['otherTrans']['signer'];
        $multi = new Multisig($pubkey, $prikey);
        $multi->set_net($net);
        $reslt = $multi->CosignTX($MultisigPubkey, $innerTransactionHash);
        $anal = $multi->analysis($reslt);
        if($anal['status']){
            // success
            echo 'Success !<BR>TX is ',$anal['txid'];
        }else{
            // false
            
        }
    }else{
        // no transaction
        echo 'no transaction';
    }
    
    
    
    
    
    