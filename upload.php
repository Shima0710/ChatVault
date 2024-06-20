<?php 
  // データベース接続情報
  $host = 'localhost';
  $dbname = 'user';
  $username = 'testuser';
  $password = 'password';

  // データベース接続
  try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $e) {
    echo "接続失敗: " . $e->getMessage();
    exit;
  }

  $msg = null;  // アップロード状況を表すメッセージ
  $alert = null;  // メッセージのデザイン用

  // アップロード処理
  if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
    $old_name = $_FILES['image']['tmp_name'];
    $new_name = date("YmdHis"); // ベースとなるファイル名は日付
    $new_name .= mt_rand(); // ランダムな数字も追加
    $size = getimagesize($_FILES['image']['tmp_name']);
    switch ($size[2]){
      case IMAGETYPE_JPEG:
        $new_name .= '.jpg';
        break;
      case IMAGETYPE_GIF:
        $new_name .= '.gif';
        break;
      case IMAGETYPE_PNG:
        $new_name .= '.png';
        break;
      default:
        header('Location: upload.php');
        exit();
    }

    // アップロードされたタイトルと日付を取得
    $title = $_POST['title'] ?? '';
    $date = $_POST['date'] ?? date("Y-m-d"); // デフォルトは現在の日付

    // 画像をアップロード
    if (move_uploaded_file($old_name, 'album/'.$new_name)){
      $msg = 'アップロードしました。';
      $alert = 'success'; // Bootstrapで緑色のボックスにする

      // データベースに画像情報を保存
      try {
        $stmt = $db->prepare("INSERT INTO images (filename, title, upload_date) VALUES (:filename, :title, :upload_date)");
        $stmt->bindParam(':filename', $new_name, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':upload_date', $date, PDO::PARAM_STR);
        $stmt->execute();
      } catch(PDOException $e) {
        $msg = 'データベースへの保存に失敗しました。';
        $alert = 'danger'; // Bootstrapで赤いボックスにする
      }

    } else {
      $msg = 'アップロードできませんでした。';
      $alert = 'danger';  // Bootstrapで赤いボックスにする
    }
  }
?>

<!doctype html>
<html lang="ja">
  <head>
    <title>サークルサイト</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  </head>
  <body>

    <?php include('navbar.php'); ?>

    <main role="main" class="container" style="padding:60px 15px 0">
      <div>
        <!-- ここから「本文」-->

        <h1>画像アップロード</h1>
        <?php
          if ($msg){
            echo '<div class="alert alert-'.$alert.'" role="alert">'.$msg.'</div>';
          }
        ?>
              
  <form action="upload.php" method="post" enctype="multipart/form-data">
  <div class="form-group">
    <label for="image">アップロードファイル</label>
    <input type="file" name="image" id="image" class="form-control-file">
  </div>
  <div class="form-group">
    <label for="title">タイトル</label>
    <input type="text" name="title" id="title" class="form-control">
  </div>
  <div class="form-group">
    <label for="date">日付</label>
    <input type="date" name="date" id="date" class="form-control">
  </div>
  <input type="submit" value="アップロードする" class="btn btn-primary">
</form>


        <!-- 本文ここまで -->
      </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="/docs/4.5/assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
