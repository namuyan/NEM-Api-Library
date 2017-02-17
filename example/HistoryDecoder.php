<?php

/* 
 * ヒストリー(ﾄﾗﾝｻﾞｸｼｮﾝ)デコーダー(TransactionBuiderとかぶるので)
 * NEMのTXは複数の種類が存在するので一括で処理できるように形式を整えるクラスを作成
 * History(TX) Decoder
 * NEM has some transaction type, so I create a class
 * to arranges the format for process all together.
 */

    require_once '../NEMApiLibrary.php';
    ?>
<style>
table.HistoryTable {
    border:#1490E9 solid 5px;
    border-collapse: collapse;
    width:100%;
}
.HistoryTable tr {
    border:#1490E9 solid 5px;
}
.HistoryTable th {
    background-color: whitesmoke;
    border: #1490E9 solid 5px;
}
.HistoryTable td {
    white-space: nowrap;
    background-color: white;
    padding: 4px;
    border: #1490E9 dotted 5px;
}
div.division {
    background-color:#67b2e8;
    margin: 30px;
    padding:10px;
}
</style>
<?php
    
    $net = 'testnet';
    // NEM Testnet Faucet を例として使用しました。
    $NEMpubkey = '47900452f5843f391e6485c5172b0e1332bf0032b04c4abe23822754214caec3';
    $NEMprikey = ''; // 暗号化されたメッセージを復号化した状態で欲しい時、必要ないならば空文字、if you want to get decoded message, or put empty str.
    $baseurl = 'http://localhost:7890';
    
    
    $his = new History($net);
    $his->setting($NEMpubkey, $NEMprikey, $baseurl);
    
    $r = 100;
    $history = array();
    while ($r > 0){
        $tmp = $his->Incoming();
        if(!$tmp){ break; }
        $history = array_merge($history, $tmp);
        $r--;
    }
    if($history){
        $transaction = $his->DecodeArray($history);
        echo '<div class="division" style="overflow-x:auto;">';
        echo '<table class="HistoryTable">';
        echo '<tr> <th>Block</th> <th>sender</th> <th>Amount</th> <th>Message</th> <th>TXhash</th> <th>Date</th></tr>';
        foreach ($transaction as $transactionValue) {
            //print '<PRE>';
            //print_r($transactionValue);
            //print '</PRE>';
            // Message処理
            if(empty($transactionValue['message']['payload'])){
                $message = '';
            }elseif($transactionValue['message']['type'] === 1){
                $message = hex2bin($transactionValue['message']['payload']);
            }elseif($transactionValue['message']['type'] === 2){
                $message = '<span style="color:red;">暗号化されています！</span>';
            }else{
                die("Error:TXMessageに例外パターンが含まれています。");
            }
                        
                        
            // 金額
            $AmountSet = '';
            if($transactionValue['txtype'] === 1){
                // XEM
                $AmountSet = '<B>'. $transactionValue['amount'] / 1000000 .' XEM</B>';
            }elseif($transactionValue['txtype'] === 2){
                // Mosaic
                foreach ($transactionValue['mosaic'] as $value) {
                    $namespace = $value['mosaicId']['namespaceId'];
                    $name = $value['mosaicId']['name'];
                    $Detail = SerchMosaicInfo($baseurl, $namespace, $name);
                    if(!$Detail){continue;}
                    $amount = $value['quantity'] / pow(10,$Detail['divisibility']);
                    $AmountSet .= '<B>'. $amount .' '. strtoupper($namespace).':'.$name.'</B><BR>';
                }
                $AmountSet = substr($AmountSet, 0, -4);
            }else{
                continue;
            }
                        
            echo '<tr>';
            echo "<td>{$transactionValue['height']}</td>";
            echo "<td>". TransactionBuilder::PubKey2Addr($transactionValue['siger']) . "</td>";
            echo "<td>$AmountSet</td>";
            echo "<td>$message</td>";
            echo "<td><a href='http://bob.nem.ninja:8765/#/transfer/{$transactionValue['hash']}' alt='nem blockexplorer'>".  substr($transactionValue['hash'], 0, 12)."...</a></td>";
            echo "<td>".date("Y-m-d H:i:s",$transactionValue['timeStamp'])."</td>";
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
        
    }else{
        echo "No history...";
    }
    
    echo '<PRE>';
    if($NEMprikey){
        echo '======================== All with decoded message ====================================';
        unset($his->pageid); // Important, reset pageid
        $r = 10;
        $history = array();
        while ($r > 0){
            $tmp = $his->AllWDM();
            if(!$tmp){ break; }
            $history = array_merge($history, $tmp);
            $r--;
        }
        if($history){
            $transaction = $his->DecodeArray($history);
            print_r($transaction);
            echo '<BR><BR>';
        }else{
            echo 'No history...<BR>';
        }
    }
    echo '======================== Outgoing ====================================';
    unset($his->pageid); // pageid は毎回リセットして下さい
    $r = 10;
    $history = array();
    while ($r > 0){
        $tmp = $his->Outgoing();
        if(!$tmp){ break; }
        $history = array_merge($history, $tmp);
        $r--;
    }
    if($history){
        $transaction = $his->DecodeArray($history);
        print_r($transaction);
        echo '<BR><BR>';
    }else{
        echo 'No history...<BR>';
    }
    echo '</PRE>';