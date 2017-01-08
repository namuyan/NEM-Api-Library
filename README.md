# NEM-Api-Library

![NEM logo](https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Nem_logo.svg/1000px-Nem_logo.svg.png)

#Overview
**NEM Api Library**を使用することにより、WEBサーバーで頻繁に使用される言語であるPHPにて*XEMの送金*、*Mosaicの送金*、*アポスティーユ*
を簡単に実現できます。

**NEM Api Library** increase convinience in your web service of PHP. For example, easy transaction build ,accessible Mosaic transfer
 and Apostille.

## Demo
exampleファイルの中にあるサンプルプログラムにより主要な動作を確認できます。  
*transferXEM.php*により基軸通貨のXEMの送金が行われ、*transferMosaic.php*によりMosaicsの送金が行われ、
*Apostille_register.php*により公証の作成、*Apostille_audit.php*により監査を実現できます。

You can test all major fanctions by examples.  
You test sending XEM by *transferXEM.php* ,test sending mosaics by *transferMosaic.php* ,
test regist a file hash by *Apostille_register.php* ,test check the file hash by *Apostille_audit.php*.

## Requirement
####作者の環境  
* XAMPP (1.8.3 include PHP Version 5.5.6)  
* NIS (NEM Beta 0.6.82)  
これより新しければ問題ないはずです。  
PHP7でも動くはずですが未確認です。


## Usage
`git clone https://github.com/namuyan/NEM-Api-Library.git`  
`NEM-Api-Library`内の`NEMApiLibrary.php`、`example`をApacheのルートフォルダ以下に加えて  
`require_once './NEMApiLibrary.php';`を使用するプログラム内に書き込むだけです。

Copy and paset `NEMApiLibrary.php` to root folder of Aparch ,and add `require_once './NEMApiLibrary.php';` on your codes.


## Install
特別なコマンドなどは必要としません。

You aren't needed any special command.


## Licence

[MIT](https://github.com/tcnksm/tool/blob/master/LICENCE)

## Author

[namuyan](http://namuyan.dip.jp)  
Twitter @namuyan_mine