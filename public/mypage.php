<?php
session_start();
require_once '../classes/UserLogic.php';
require_once '../functions.php';

// ログインしているか判定し、していなかったら新規登録画面へ返す
$result = UserLogic::checkLogin();

if (!$result) {
    $_SESSION['login_err'] = 'ユーザを登録してログインしてください！';
    header('Location: signup_form.php');
    return;
}

$login_user = $_SESSION['login_user'];



//変数の初期化
$message = array();
$message_array = array();
$success_message = null;
$error_message = array();
$escaped = array();
$pdo = null;
$statment = null;
$res = null;

//データベース接続
try {
    $pdo = new PDO('mysql:charset=UTF8;dbname=summer assignemnt;host=localhost', 'useraccount', 'userpass');
} catch (PDOException $e) {
    //接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

//送信して受け取ったデータは$_POSTの中に自動的に入る。
//投稿データがあるときだけログを表示する。
if (!empty($_POST["submitButton"])) {

    $escaped['username'] = $login_user['name'];

    //コメントの入力チェック
    if (empty($_POST["comment"])) {
        $error_message[] = "コメントを入力してください。";
    } else {
        $escaped['comment'] = htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8");
    }

    //エラーメッセージが何もないときだけデータ保存できる
    if (empty($error_message)) {

        //トランザクション開始
        $pdo->beginTransaction();

        try {

            //SQL作成
            $statment = $pdo->prepare("INSERT INTO items (username, comment) VALUES (:username, :comment)");

            //値をセット
            $statment->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
            $statment->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);

            //SQLクエリの実行
            $res = $statment->execute();

            //ここまでエラーなくできたらコミット
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラーが発生したときはロールバック(処理取り消し)
            $pdo->rollBack();
        }

        if ($res) {
            $success_message = "コメントを書き込みました。";
        } else {
            $error_message[] = "書き込みに失敗しました。";
        }

        $statment = null;
    }
}


//DBからコメントデータを取得する
$sql1 = "SELECT username, comment FROM items";
$message_array = $pdo->query($sql1);


//DB接続を閉じる
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
</head>

<body>
    <h1>簡易掲示板</h1>
    <hr>
    <div>
        <!-- メッセージ送信成功時 -->
        <?php if (!empty($success_message)) : ?>
            <p class="success_message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <!-- バリデーションチェック時 -->
        <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
                <div class="error_message">※<?php echo $value; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <section>
            <?php if (!empty($message_array)) : ?>
                <?php foreach ($message_array as $value) : ?>
                    <article>
                        <div>
                            <div>
                                <span>名前： <?php echo $value['username'] ?></span>
                            </div>
                            <p><?php echo $value['comment']; ?></p>
                            <div>
                                <p>----------------------------------------------------------------------------------</p>
                            </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <form method="POST" action="">
            <div>
                <input type="submit" value="書き込む" name="submitButton">
                <label for="usernameLabel">名前：　<?php echo h($login_user['name']) ?></label>
            </div>
            <div>
                <textarea name="comment"></textarea>
            </div>
        </form>
    </div>

<form action="logout.php" method="POST">
<input type="submit" name="logout" value="ログアウト">
</form>
</body>
</html>