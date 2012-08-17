basecontroller
==============

あなたのコントローラに書くべき処理を省略して自動化するためのコントローラのパッケージ。

あなたのコントローラから継承して使う。

## Controller_AutoResponse
- afterメソッドでreturn Response::forge(ViewModel->forge('xxx/yyy'))する。
 - xxx/yyyはコントローラ名とactionメソッド名から自動で決められる。

- Controllerを継承している。

## Controller_SimpleAuth
- beforeメソッドでSimpleAuthパッケージのAuth::check()を行う。
- Controller_AutoResponseを継承している。

## pageタスク
- 新規ページに必要なひな形ファイルを生成する。
 - コントローラを生成し、アクションメソッドを追加。
 - モジュール下のコントローラを指定する場合は「モジュール名:コントローラ名」とする。
 - アクションメソッドに対応するビューモデルを生成。
 - アクションメソッドに対応するビューを生成（smartyのテンプレートのみ対応）。
 - ビューモデルとビューはコントローラごとに生成され、それぞれ前述のクラスから継承される。

    oil r page:create controller action1 action2..
    oil r page:create module:controller action1 action2..