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
use Blog\Models\Comment;
use Blog\Models\Post;

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['code' => 0, 'msg' => '请先登录']);
    exit;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

$commentId = (int)($_REQUEST['id'] ?? 0);
$comment = Comment::findById($commentId);
if (!$comment) {
    echo json_encode(['code' => 0, 'msg' => '评论不存在']);
    exit;
}

$post = Post::find($comment->postId);
$isCommentAuthor = $_SESSION['user_id'] == $comment->userId;
$isPostAuthor = $_SESSION['user_id'] == $post->userId;

if (!$isCommentAuthor && !$isPostAuthor) {
    echo json_encode(['code' => 0, 'msg' => '无权删除']);
    exit;
}

$comment->delete();

if ($isAjax) {
    echo json_encode(['code' => 1, 'msg' => '删除成功']);
    exit;
} else {
    header("Location: view_post.php?id=".$comment->postId, true, 302);
    exit;
}