<?php
namespace Blog\Models;
use Blog\Core\Database;

class Post
{
    public ?int $id = null;
    public int $userId = 0;
    public string $title = '';
    public ?string $cover = null;
    public string $content = '';
    public string $createdAt = '';
    public string $authorName = '';

    // 分页获取文章列表
    public static function getPaginated(int $page = 1, int $perPage = 5): array
    {
        $pdo = Database::getInstance()->getPdo();
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username as authorName
             FROM posts p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $list = [];
        while ($row = $stmt->fetch()) {
            $post = new self();
            $post->id = (int)$row['id'];
            $post->userId = (int)$row['user_id'];
            $post->title = $row['title'];
            $post->cover = $row['cover'];
            $post->content = $row['content'];
            $post->createdAt = $row['created_at'];
            $post->authorName = $row['authorName'] ?? '匿名用户';
            $list[] = $post;
        }
        return $list;
    }

    // 获取文章总数
    public static function getTotalCount(): int
    {
        $pdo = Database::getInstance()->getPdo();
        return (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    }

    // 根据ID查询单篇文章
    public static function find(int $id): ?self
    {
        $pdo = Database::getInstance()->getPdo();
        $sql = "
            SELECT p.*, u.username as authorName
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :pid
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $post = new self();
        $post->id = $row['id'];
        $post->userId = $row['user_id'];
        $post->title = $row['title'];
        $post->cover = $row['cover'];
        $post->content = $row['content'];
        $post->createdAt = $row['created_at'];
        $post->authorName = $row['authorName'] ?? '匿名用户';
        return $post;
    }

    // 保存：新增/更新
    public function save(): self
    {
        $pdo = Database::getInstance()->getPdo();
        if ($this->id) {
            $stmt = $pdo->prepare("UPDATE posts SET title=:t, cover=:cover, content=:c WHERE id=:id");
            $stmt->execute([
                ':t' => $this->title,
                ':cover' => $this->cover,
                ':c' => $this->content,
                ':id' => $this->id
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, cover, content) VALUES (:uid, :t, :cover, :c)");
            $stmt->execute([
                ':uid' => $this->userId,
                ':t' => $this->title,
                ':cover' => $this->cover,
                ':c' => $this->content
            ]);
            $this->id = (int)$pdo->lastInsertId();
        }
        return $this;
    }

    // 删除文章
    public function delete(): void
    {
        if (!$this->id) return;
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
    }
}