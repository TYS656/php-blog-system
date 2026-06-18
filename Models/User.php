<?php
namespace Blog\Models;
use Blog\Core\Database;

class User
{
    public ?int $id = null;
    public string $username = '';
    public string $email = '';
    public ?string $avatar = null;

    // 根据用户名查询
    public static function findByUsername(string $username): ?self
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT id, username, email, avatar FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $data = $stmt->fetch();
        if (!$data) return null;

        $user = new self();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->avatar = $data['avatar'];
        return $user;
    }

    // 根据ID查询（含密码）
    public static function findById(int $id): ?self
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT id, username, email, avatar, password FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        if (!$data) return null;

        $user = new self();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->avatar = $data['avatar'];
        return $user;
    }

    // 根据邮箱查询
    public static function findByEmail(string $email): ?self
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT id, username, email, avatar FROM users WHERE email = :e");
        $stmt->execute([':e' => $email]);
        $data = $stmt->fetch();
        if (!$data) return null;

        $user = new self();
        $user->id = $data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->avatar = $data['avatar'];
        return $user;
    }

    // 注册新用户
    public static function create(string $username, string $password, string $email, ?string $avatarPath = null): int
    {
        $pdo = Database::getInstance()->getPdo();
        $hashPwd = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password, email, avatar) VALUES (:u, :p, :e, :avatar)"
        );
        $stmt->execute([
            ':u' => $username,
            ':p' => $hashPwd,
            ':e' => $email,
            ':avatar' => $avatarPath
        ]);
        return (int)$pdo->lastInsertId();
    }

    // 校验密码
    public function verifyPassword(string $inputPwd): bool
    {
        if (!$this->id) return false;
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
        $row = $stmt->fetch();
        return $row && password_verify($inputPwd, $row['password']);
    }

    // 校验用户名/邮箱是否存在
    public static function isExists(string $username, string $email): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
        $stmt->execute([':u' => $username, ':e' => $email]);
        return (bool)$stmt->fetch();
    }

    // 更新个人资料
    public function updateProfile(string $email, ?string $avatar = null): bool
    {
        if (!$this->id) return false;
        $pdo = Database::getInstance()->getPdo();
        
        if ($avatar !== null) {
            $stmt = $pdo->prepare("UPDATE users SET email = :e, avatar = :avatar WHERE id = :id");
            $stmt->execute([':e' => $email, ':avatar' => $avatar, ':id' => $this->id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = :e WHERE id = :id");
            $stmt->execute([':e' => $email, ':id' => $this->id]);
        }
        return true;
    }

    // 修改密码（需原密码）
    public function updatePassword(string $oldPwd, string $newPwd): bool
    {
        if (!$this->id) return false;
        if (!$this->verifyPassword($oldPwd)) return false;
        
        $pdo = Database::getInstance()->getPdo();
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
        $stmt->execute([':p' => $hash, ':id' => $this->id]);
        return $stmt->rowCount() > 0;
    }

    // 重置密码（无需原密码，忘记密码用）
    public static function resetPasswordByEmail(string $email, string $newPwd): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :p WHERE email = :e");
        $stmt->execute([':p' => $hash, ':e' => $email]);
        return $stmt->rowCount() > 0;
    }

    // 创建密码重置令牌
    public static function createResetToken(string $email): string
    {
        $pdo = Database::getInstance()->getPdo();
        $token = bin2hex(random_bytes(32));
        $expire = time() + 1800;

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :e");
        $stmt->execute([':e' => $email]);

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expire_time) VALUES (:e, :t, :exp)");
        $stmt->execute([':e' => $email, ':t' => $token, ':exp' => $expire]);
        return $token;
    }

    // 校验重置令牌
    public static function validateResetToken(string $token): ?string
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT email, expire_time FROM password_resets WHERE token = :t LIMIT 1");
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();
        if (!$row || $row['expire_time'] < time()) return null;
        return $row['email'];
    }

    // 删除重置令牌
    public static function deleteResetToken(string $token): void
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :t");
        $stmt->execute([':t' => $token]);
    }

    // 删除账号
    public function delete(): void
    {
        if (!$this->id) return;
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $this->id]);
    }
}