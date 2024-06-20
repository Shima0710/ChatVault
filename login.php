<?php
session_start(); // セッション開始

if (isset($_SESSION['id'])) {
    // セッションにユーザIDがある=ログインしている
    // ログイン済ならトップページに遷移する
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTリクエストがある場合、ログインを試みる

    // 入力されたユーザー名とパスワードを取得
    $name = $_POST['name'];
    $password = $_POST['password'];

    // データベースに接続
    $dsn = 'mysql:host=localhost;dbname=user;charset=utf8';
    $db_user = 'testuser'; // データベースのユーザ名
    $db_password = 'password'; // データベースのパスワード

    try {
        // PDOインスタンスを生成
        $db = new PDO($dsn, $db_user, $db_password);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
        // ユーザー名に対応するパスワードを取得
        $stmt = $db->prepare("SELECT id, password FROM users WHERE name = :name");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();
    
        if ($user && password_verify($password, $user['password'])) {
            // パスワードが一致した場合、ログイン成功

            // セッションID再作成
            session_regenerate_id(true);

            // ユーザIDをセッションに保存
            $_SESSION['id'] = $user['id'];

            // ログイン後のページにリダイレクト
            header('Location: index.php');
            exit();
        } else {
            // パスワードが一致しない場合、ログイン失敗
            $error_message = "ユーザ名またはパスワードが正しくありません。";
        }
    } catch (PDOException $e) {
        exit('エラー：' . $e->getMessage());
    }
}
?>

<!doctype html>
<html lang="ja">
<head>
    <title>ログイン</title>
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
        <form action="login.php" method="post">
            <h1>ログイン</h1>
            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <label class="sr-only">ユーザ名</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="ユーザ名" required autofocus>
            <label class="sr-only">パスワード</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="パスワード" required>
            <input type="submit" class="btn btn-primary btn-block" value="ログイン">
        </form>
        <br>
        <p class="text-center">アカウントをお持ちでない方は <a href="register.php">こちら</a> から新規会員登録を行ってください。</p>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
<script>window.jQuery || document.write('<script src="/docs/4.5/assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
