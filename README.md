# NEM-Api-Library

![NEM logo](https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Nem_logo.svg/1000px-Nem_logo.svg.png)


## Important  
**Ⅾevelopment of this library has stopped. We recommend 2 libararys.**  
**[lezhnev74\/NEM-Api-Library](https://github.com/lezhnev74/NEM-Api-Library) or [evias\/nem-php](https://github.com/evias/nem-php)**

## Overview  
**NEM Api Library**を使用することにより、WEBサーバーで頻繁に使用される言語であるPHPにて*XEMの送金*、*Mosaicの送金*、*アポスティーユ*
、*Multisig* を簡単に実現できます。

**NEM Api Library** increase convinience in your web service of PHP. For example, *easy transaction build* ,  
*accessible Mosaic transfer* ,*Apostille* and *Multisig*.

## Demo
exampleファイルの中にあるサンプルプログラムにより主要な動作を確認できます。  
*transferXEM.php*により基軸通貨のXEMの送金が行われ、*transferMosaic.php*によりMosaicsの送金が行われ、
*Apostille_register.php*により公証の作成、*Apostille_audit.php*により監査を実現できます。

また、マルチシグで使用するトランザクションの生成もできます。マルチシグへの変換、マルチシグより送金(XEM,Mosaics)、  
連署者として署名、マルチシグアカウントの編集です。(2017/1 added)

You can test all major fanctions by examples.  
You test sending XEM by *transferXEM.php* ,test sending mosaics by *transferMosaic.php* ,  
test regist a file hash by *Apostille_register.php* ,test check the file hash by *Apostille_audit.php*.

And you can create a transaction ,for example create Multisig account ,initiate multisig transaction ,
transfer XEM and Mosaics from multisig ,sign as co-signer and modify multisig account.(2017/1 added)

## Requirement
####作者の環境  
* XAMPP (1.8.3 include PHP Version 5.5.6)  
* NIS (NEM Beta 0.6.82)  
これより新しければ問題ないはずです。  
PHP7でも動くはずですが未確認です。

I recommend PHP5 ,but it may work on PHP7.


## Install
`git clone https://github.com/namuyan/NEM-Api-Library.git`

*NEM-Api-Library* 内の *NEMApiLibrary.php* をApacheのルートフォルダ以下に加えて  
`require_once './NEMApiLibrary.php';` を使用するプログラム内に書き込むだけです。  
特別なコマンドなどは必要としません。パスは適宜設定してください。

Copy and paset `NEMApiLibrary.php` to root folder of Aparch ,and add `require_once './NEMApiLibrary.php';` on your codes.  
You aren't needed any special command.

## Usage
*example*フォルダ内のコメントを見て下さい。  
Look at example folders.

## Log
2017-01-08 first commit  
2017-01-12 Add Multisig function  
2017-02-17 Add Transaction Decoder, add Cashe system and fix errors

## Licence

[MIT](https://github.com/tcnksm/tool/blob/master/LICENCE)

## Author

[namuyan](http://namuyan.dip.jp)  
Twitter @namuyan_mine

DonationCPaddress： 1BvRTmPCe47vee2CyrLi9AGeSEcrR2ciM4  
DonationNEMaddress： NAN7XFG52NL3V5AW3NTSYO77AVR6X5LYRJKXWKHY  
DonationMonacoin： MSYTEF7t62b9sjXt3oN9JokSjnYkvtcPFx  
