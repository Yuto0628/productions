<?php
    #セッションの開始
    session_start();
    ini_set('display_errors',1);
    error_reporting(E_ALL);

    #ボタンが押されたら実行
    if(isset($_POST['add_post'])){
        #セッションに入力データを登録する
        if(isset($_POST['add_post'])){
            $_SESSION['add_y'] = (int)$_POST['add_y'];
            $_SESSION['add_m'] = (int)$_POST['add_m'];
            $_SESSION['add_d'] = (int)$_POST['add_d'];
            $_SESSION['add_content'] = $_POST['add_content'];
        }
        #reservation.phpに遷移
        header('location: reservation.php');
    }
?>
<DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>PHP_TEST</title>
    </head>
    <body>
      <h1>データ受け取りプログラム</h1>
      <p>日付と予定を入力してください</p>
      <form action="" method="post">
            <input type="text" name="add_y">年
            <input type="text" name="add_m">月
            <input type="text" name="add_d">日<br>
            予定:<input type="text" name="add_content"><br>
            <input type="submit" name="add_post">
      </form>
    </body>
</html>