<?php

/* 
 * アポスティーユ
 * P2Pネットワークにファイルのハッシュを登録します。
 * これによりその他大勢に対して、ファイルの作成者、登録日時、内容を保証します。
 * 動作確認はTestnetでお願いします。
 * Apostille
 * This sample proglam register a file hash.
 * It guarantee the creator ,the date and the contents for other majorities.
 * Please test on testnet!
 */


    require_once '../NEMApiLibrary.php';
    
    $net = 'testnet';
    $NEMpubkey = '';
    $NEMprikey = '';
    $baseurl = 'http://localhost:7890';
    $filename = __DIR__ .'/NEM_logo.png'; // 公証するもの、サンプルのロゴ。
    $dir = __DIR__ .'/';
    $type = 'public'; // 只今の所、publicのみ対応。
    $algo = 'sha256'; // SHA3も可能ですがライブラリが貧弱であるためかなり遅いです。
                      // 使用可能な早さであるか確認してから使用を検討してください。
                      // PHPはネイティブでSHA256までです。
                      // 外部モジュールを導入することもできますが、SHA3採用前のkeccakであることにご注意ください
    
    $apo = new Apostille();
    $apo->setting($filename, $type, $algo, $net);
    $apo->Run();
    $reslt = $apo->send($NEMpubkey, $NEMprikey, $baseurl);
    $out = $apo->Outfile($dir); // nanowalletで監査可能な形式で$dirへ出力します。
    
    
    if($reslt['status']){
        echo '<P>','Fee is ',$reslt['fee'],' XEM<BR>';
        echo       'TXID is ',$reslt['txid'],'<BR>';
        echo       'Output is ',$out,' ,1=true 0=false</P>';
    }else{
        echo 'Error message: ',$reslt['message'];
    }