<?php
    #セッションの開始
    session_start();

    #セッションに登録していた値を受け取る
    $add_y = $_SESSION['add_y'];
    $add_m = $_SESSION['add_m'];
    $add_d = $_SESSION['add_d'];
    $add_content = $_SESSION['add_content'];

    #データベースに接続
    $pdo = new  PDO('mysql:host=localhost;dbname=calendar;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false));

    #フォームから送られたデータをdate型に整形
    $date = $add_y.'-'.$add_m.'-'.$add_d;

    #データベースにレコードを登録
    $stmt = $pdo -> prepare("INSERT INTO todo(date, content) VALUES(:date, :content)");
    $stmt -> bindValue(':date', $date, PDO::PARAM_STR);
    $stmt -> bindValue(':content', $add_content, PDO::PARAM_STR);
    $stmt -> execute();

    #セッションの値を初期化
    $_SESSION['add_y'] = null;
    $_SESSION['add_m'] = null;
    $_SESSION['add_d'] = null;
    $_SESSION['add_content'] = null;

    #データベースへの接続を終了
    $pdo = null;

    #index.phpに遷移
    header('location: index.php');
?>