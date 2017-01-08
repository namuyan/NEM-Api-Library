<?php

/* 
 * アポスティーユ
 * P2Pネットワークに登録されたファイルのハッシュを基に監査します。
 * これによりその他大勢に対して、ファイルの作成者、登録日時、内容を保証します。
 * 動作確認はTestnetでお願いします。
 * Apostille
 * This sample proglam audit a file hash.
 * It guarantee the creator ,the date and the contents for other majorities.
 * Please test on testnet!
 */

    require_once '../NEMApiLibrary.php';
    
    $net = 'testnet';
    $NEMpubkey = '';
    $NEMprikey = '';
    $baseurl = 'http://localhost:7890';
    $filename = __DIR__ .'/NEM_logo -- Apostille TX 08806288160138ce29cc8f6817466670cb697b456d4fa1c7beb8986b3b64c464 -- Date 2017-01-07.png'; // 公証するもの、サンプルのロゴ。
    
    $apo = new Apostille($filename);
    $apo->set_net($net); // mainnetならば必要ない
    $reslt = $apo->Check($baseurl);
    $anal = $apo->analysis($reslt,$baseurl);
    $algo = $apo->algo;
    
    if($reslt['status']){
        echo '<P>Audit success !','<BR>date is ',date("Y-m-d H:i:s", $anal['timeStamp']),'<BR>creator is ',$anal['creator'],'<BR>algo is ',$algo,'</P>';
        echo '<PRE>';
        print_r($reslt['detail']);
        echo '</PRE>';
    }else{
        echo '<P>Audit fail</P>';
        echo 'code is ',$reslt['code'],'<BR>';
        echo 'code 0 is success, 1 is cannnot get data from nodes, 2 is something wrong with message.';
    }