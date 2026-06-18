<?php
spl_autoload_register(function ($className) {
    $prefix = 'Blog\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    if (str_starts_with($className, $prefix)) {
        $relativeClass = substr($className, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) require $file;
    }
});
session_start();
if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}
use Blog\Models\Post;

$pid = (int)($_GET['id'] ?? 0);
$post = Post::find($pid);
if(!$post || $post->userId != $_SESSION['user_id']){
    header("Location: home.php", true, 302);
    exit;
}

if (empty($_SESSION['csrf_edit'])) {
    $_SESSION['csrf_edit'] = bin2hex(random_bytes(32));
}
$errors = [];
$uploadDir = __DIR__ . '/uploads/post/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_edit'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "非法请求";
    }
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $newCover = $post->cover;

    if (empty($title)) $errors[] = "标题不能为空";
    if (empty($content)) $errors[] = "正文不能为空";

    // 处理新封面上传
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (in_array($mime, ['image/jpeg','image/png']) && $file['size'] <= 2097152) {
            $ext = $mime === 'image/png' ? 'png' : 'jpg';
            $name = md5(uniqid(true)).".".$ext;
            move_uploaded_file($file['tmp_name'], $uploadDir.$name);
            $newCover = "uploads/post/".$name;
        } else {
            $errors[] = "封面仅支持jpg/png，不超过2MB";
        }
    }

    if (empty($errors)) {
        $post->title = $title;
        $post->cover = $newCover;
        $post->content = $content;
        $post->save();
        header("Location: view_post.php?id=$pid", true, 302);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>编辑文章</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f5f7fa;padding:60px 20px;}
.card{width:720px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);}
h2{text-align:center;margin-bottom:24px;color:#2d3748;}
.err-list{background:#fee;color:#dc2626;padding:10px 16px;border-radius:6px;margin-bottom:16px;}
.item{margin-bottom:18px;}
label{display:block;margin-bottom:6px;color:#4a5568;font-weight:500;}
input[type="text"]{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
textarea{width:100%;padding:12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;resize:vertical;min-height:300px;line-height:1.6;}
.cover-preview{max-width:200px;margin:8px 0;border-radius:6px;}
.tip{font-size:12px;color:#999;margin-top:4px;}
button{width:100%;padding:12px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
.back{display:block;text-align:center;margin-top:16px;color:#666;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>编辑文章</h2>
    <?php if(!empty($errors)): ?>
        <div class="err-list">
            <?php foreach($errors as $e): ?>
            <li style="list-style-position:inside;"><?=htmlspecialchars($e)?></li>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_edit'])?>">
        
        <div class="item">
            <label>文章标题</label>
            <input type="text" name="title" value="<?=htmlspecialchars($post->title)?>">
        </div>

        <div class="item">
            <label>文章封面</label>
            <?php if(!empty($post->cover) && file_exists($post->cover)): ?>
                <img class="cover-preview" src="<?=htmlspecialchars($post->cover)?>" alt="当前封面">
            <?php endif; ?>
            <input type="file" name="cover" accept="image/jpeg,image/png">
            <p class="tip">不上传则保留原封面</p>
        </div>

        <div class="item">
            <label>文章正文</label>
            <textarea name="content"><?=htmlspecialchars($post->content)?></textarea>
        </div>

        <button type="submit">保存修改</button>
    </form>
    <a class="back" href="view_post.php?id=<?=$pid?>">返回文章详情</a>
</div>
</body>
</html>