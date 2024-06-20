<?php
session_start(); // セッション開始

if (isset($_SESSION['id'])) {
    // セッションにユーザIDがある=ログインしている
    // ログイン済ならトップページに遷移する
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTリクエストがある場合、新規ユーザーを作成する

    // 入力されたユーザー名とパスワードを取得
    $name = $_POST['name'];
    $password = $_POST['password'];

    // パスワードをbcryptでハッシュ化
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // データベースに接続
    $dsn = 'mysql:host=localhost;dbname=user;charset=utf8';
    $db_user = 'testuser'; // データベースのユーザ名
    $db_password = 'password'; // データベースのパスワード

    try {
        // PDOインスタンスを生成
        $db = new PDO($dsn, $db_user, $db_password);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
        // ユーザーをデータベースに追加
        $stmt = $db->prepare("INSERT INTO users (name, password) VALUES (:name, :password)");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->execute();
    
        // 登録成功したらログインページにリダイレクト
        $_SESSION['name'] = $name; // ユーザ名をセッションに保存
        header('Location: login.php');
        exit();
    } catch (PDOException $e) {
        exit('エラー：' . $e->getMessage());
    }
}
?>

<!doctype html>
<html lang="ja">
<head>
    <title>新規会員登録</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style type="text/css">
        form {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
            text-align: center;
        }
        #name {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        #password {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body>

<main role="main" class="container" style="padding:60px 15px 0">
    <div>
        <!-- ここから「本文」-->

        <form action="register.php" method="post">
            <h1>新規会員登録</h1>
            <label class="sr-only">ユーザ名</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="ユーザ名" required autofocus>
            <label class="sr-only">パスワード</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="パスワード" required>
            <input type="submit" class="btn btn-primary btn-block" value="登録">
        </form>

        <!-- 本文ここまで -->
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="/docs/4.5/assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

