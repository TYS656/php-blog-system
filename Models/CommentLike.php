<?php
namespace Blog\Models;
use Blog\Core\Database;

class CommentLike
{
    /**
     * 获取单条评论的点赞总数
     */
    public static function getCount(int $commentId): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = :cid");
        $stmt->execute([':cid' => $commentId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 判断当前用户是否已点赞该评论
     */
    public static function checkUserLike(int $commentId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM comment_likes WHERE comment_id = :cid AND user_id = :uid LIMIT 1"
        );
        $stmt->execute([':cid' => $commentId, ':uid' => $userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * 切换点赞状态：点赞→取消，取消→点赞
     * 返回 true 为当前已点赞，false 为已取消
     */
    public static function toggle(int $commentId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $isLiked = self::checkUserLike($commentId, $userId);

        if ($isLiked) {
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = :cid AND user_id = :uid");
            $stmt->execute([':cid' => $commentId, ':uid' => $userId]);
            return false;
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (:cid, :uid)");
                $stmt->execute([':cid' => $commentId, ':uid' => $userId]);
                return true;
            } catch (\PDOException $e) {
                return true;
            }
        }
    }
}