<?php
namespace Blog\Models;
use Blog\Core\Database;

class Notification
{
    public ?int $id = null;
    public int $userId = 0;
    public string $type = '';
    public ?int $relatedPostId = null;
    public int $triggerUserId = 0;
    public string $triggerUserName = '';
    public string $content = '';
    public int $isRead = 0;
    public string $createdAt = '';

    // 发送通知
    public static function send(int $userId, string $type, int $triggerUserId, int $postId, string $content): void
    {
        if ($userId === $triggerUserId) return;
        
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, related_post_id, trigger_user_id, content) 
             VALUES (:uid, :type, :pid, :tuid, :content)"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':type' => $type,
            ':pid' => $postId,
            ':tuid' => $triggerUserId,
            ':content' => $content
        ]);
    }

    // 获取未读数量
    public static function getUnreadCount(int $userId): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    // 获取全部通知
    public static function getUserAll(int $userId): array
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT n.*, u.username as triggerUserName 
             FROM notifications n
             JOIN users u ON n.trigger_user_id = u.id
             WHERE n.user_id = :uid
             ORDER BY n.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $item = new self();
            $item->id = (int)$row['id'];
            $item->userId = (int)$row['user_id'];
            $item->type = $row['type'];
            $item->relatedPostId = (int)$row['related_post_id'];
            $item->triggerUserId = (int)$row['trigger_user_id'];
            $item->triggerUserName = $row['triggerUserName'];
            $item->content = $row['content'];
            $item->isRead = (int)$row['is_read'];
            $item->createdAt = $row['created_at'];
            $list[] = $item;
        }
        return $list;
    }

    // 全部标记已读
    public static function markAllRead(int $userId): void
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
    }
    /**
     * 清空当前用户所有通知
     */
    public static function clearAll(int $userId): void
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
    }

    /**
     * 发送评论点赞通知
     */
    public static function sendCommentLike(int $commentAuthorId, int $triggerUserId, int $postId, string $content): void
    {
        if ($commentAuthorId === $triggerUserId) return;
        
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, related_post_id, trigger_user_id, content) 
            VALUES (:uid, 'comment_like', :pid, :tuid, :content)"
        );
        $stmt->execute([
            ':uid' => $commentAuthorId,
            ':pid' => $postId,
            ':tuid' => $triggerUserId,
            ':content' => $content
        ]);
    }
}