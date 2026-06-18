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
use Blog\Models\CommentLike;
use Blog\Models\Comment;
use Blog\Models\Notification;

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['code' => 0, 'msg' => '请先登录']);
    exit;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_interact'] ?? '', $token)) {
        echo json_encode(['code' => 0, 'msg' => '非法请求']);
        exit;
    }
    
    if ($commentId <= 0) {
        echo json_encode(['code' => 0, 'msg' => '参数错误']);
        exit;
    }

    $isLikedAfter = CommentLike::toggle($commentId, $_SESSION['user_id']);
    $likeCount = CommentLike::getCount($commentId);
    $comment = Comment::findById($commentId);
    $postId = $comment ? $comment->postId : 0;

    // 点赞成功时发送通知
    if ($isLikedAfter && $comment) {
        $shortContent = mb_substr($comment->content, 0, 15);
        if (mb_strlen($comment->content) > 15) $shortContent .= '…';
        $content = "赞了你的评论：「".$shortContent."」";
        Notification::sendCommentLike($comment->userId, $_SESSION['user_id'], $postId, $content);
    }

    if ($isAjax) {
        echo json_encode([
            'code' => 1,
            'is_liked' => $isLikedAfter,
            'count' => $likeCount
        ]);
        exit;
    } else {
        header("Location: view_post.php?id=$postId", true, 302);
        exit;
    }
}