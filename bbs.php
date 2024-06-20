<?php
  include 'includes/login.php';
  // 1ページに表示される書き込みの数
  $num = 10;

  // DBに接続
  $dsn = 'mysql:host=localhost;dbname=user;charset=utf8';
  $user = 'testuser';
  $password = 'password';

  // GETメソッドで2ページ目以降が指定されているとき
  $page = 1;
  if (isset($_GET['page']) && $_GET['page'] > 1){
    $page = intval($_GET['page']);
  }

  try {
    // PDOインスタンスの生成
    $db = new PDO($dsn, $user, $password);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // プリペアドステートメントを作成
    $stmt = $db->prepare("SELECT * FROM bbs WHERE parent_id IS NULL ORDER BY date DESC LIMIT :page, :num");
    // パラメータを割り当て
    $page = ($page-1) * $num;
    $stmt->bindParam(':page', $page, PDO::PARAM_INT);
    $stmt->bindParam(':num', $num, PDO::PARAM_INT);
    // クエリの実行
    $stmt->execute();
  } catch (PDOException $e){
    exit("エラー：" . $e->getMessage());
  }

  // レスポンスの処理
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTリクエストがある場合、レスポンスを作成する

    // 入力された名前と本文を取得
    $name = $_POST['name'];
    $body = $_POST['body'];
    $parent_id = $_POST['parent_id'];

    try {
      // プリペアドステートメントを作成
      $stmt = $db->prepare("INSERT INTO bbs (name, body, parent_id) VALUES (:name, :body, :parent_id)");
      // パラメータを割り当て
      $stmt->bindParam(':name', $name, PDO::PARAM_STR);
      $stmt->bindParam(':body', $body, PDO::PARAM_STR);
      $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
      // クエリの実行
      $stmt->execute();

      // レスポンスを追加したらページをリロード
      header("Location: {$_SERVER['PHP_SELF']}?page={$page}");
      exit();
    } catch (PDOException $e) {
      exit("エラー：" . $e->getMessage());
    }
  }
?>
<!doctype html>
<html lang="ja" >
  <head>
    <title>サークルサイト</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  </head>
  <body>

    <?php include('navbar.php'); ?>

    <main role="main" class="container" style="padding:60px 15px 0">
      <div>
        <!-- ここから「本文」-->

        <h1>掲示板</h1>
        <form action="write.php" method="post">
          <div class="form-group">
            <label>タイトル</label>
            <input type="text" name="title" class="form-control">
          </div>
          <div class="form-group">
            <label>名前</label>
            <input type="text" name="name" class="form-control" value="<?php echo $_SESSION['name'] ?>">
          </div>
          <div class="form-group">
            <textarea name="body" class="form-control" rows="5"></textarea>
          </div>
          <div class="form-group">
            <label>削除パスワード（数字４桁）</label>
            <input type="text" name="pass" class="form-control">
          </div>
          <input type="submit" class="btn btn-primary" value="書き込む">
          <input type="hidden" name="token" value="<?php echo hash("sha256", session_id()) ?>">
        </form>
        <hr>

<?php while ($row = $stmt->fetch()): ?>
        <div class="card">
          <div class="card-header"><?php echo $row['title']? $row['title']: '（無題）'; ?></div>
          <div class="card-body">
            <p class="card-text"><?php echo nl2br(htmlspecialchars($row['body'], ENT_QUOTES, 'UTF-8')) ?></p>
          </div>
          <div class="card-footer mt-2">
  <form action="delete.php" method="post" class="form-inline">
    <?php echo $row['name'] ?>
    (<?php echo $row['date'] ?>)
    <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
    <input type="text" name="pass" placeholder="削除パスワード" class="form-control">
    <input type="submit" value="削除" class="btn btn-secondary">
    <input type="hidden" name="token" value="<?php echo hash("sha256", session_id()) ?>">
  </form>
  <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" class="form-inline mt-2">
    <input type="hidden" name="parent_id" value="<?php echo $row['id'] ?>">
    <input type="text" name="name" placeholder="名前" class="form-control"value="<?php echo $_SESSION['name'] ?>">
    <textarea name="body" placeholder="レス内容" class="form-control" rows="1"></textarea>
    <input type="submit" value="レス" class="btn btn-primary">
    <input type="hidden" name="token" value="<?php echo hash("sha256", session_id()) ?>">
  </form>

            <!-- レスポンスの表示 -->
            <?php
              // レスポンスを取得するためのクエリ
              $response_query = $db->prepare("SELECT * FROM bbs WHERE parent_id = :parent_id ORDER BY date ASC");
              $response_query->bindParam(':parent_id', $row['id'], PDO::PARAM_INT);
              $response_query->execute();

              // レスポンスがある場合は表示
              while ($response_row = $response_query->fetch()):
            ?>
              <div class="card mt-2">
                <div class="card-body">
                  <p class="card-text"><?php echo nl2br(htmlspecialchars($response_row['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
                <div class="card-footer">
                  <small class="text-muted"><?php echo $response_row['name']; ?> (<?php echo $response_row['date']; ?>)</small>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
        <hr>
<?php endwhile; ?>

<?php
  // ページ数の表示
  try {
    // プリペアドステートメントの作成
    $stmt = $db->prepare("SELECT COUNT(*) FROM bbs");
    // クエリの実行
    $stmt->execute();
  } catch (PDOException $e){
    exit("エラー：" . $e->getMessage());
  }

  // 書き込みの件数を取得
  $comments = $stmt->fetchColumn();
  // ページ数を計算
  $max_page = ceil($comments / $num);
  // ページングの必要性があれば表示
  if ($max_page >= 1){
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $max_page; $i++){
      echo '<li class="page-item"><a class="page-link" href="bbs.php?page='.$i.'">'.$i.'</a></li>';
    }
    echo '</ul></nav>';
  }
?>

        <!-- 本文ここまで -->
      </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="/docs/4.5/assets/js/vendor/jquery-slim.min.js"><\/script>')</script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
