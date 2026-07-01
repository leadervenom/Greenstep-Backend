<?php
namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    // -------------------------------------------------------------------------
    // AUTH METHODS
    // -------------------------------------------------------------------------

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = :e'
        );
        $stmt->execute([':e' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, role FROM users WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $name, string $email, string $hash, string $role = 'member'): int
    {
        $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:n, :e, :h, :r)'
        )->execute([
            ':n' => trim($name),
            ':e' => mb_strtolower(trim($email)),
            ':h' => $hash,
            ':r' => $role
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :e');
        $stmt->execute([':e' => mb_strtolower(trim($email))]);
        return (bool)$stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // FRIEND METHODS
    // -------------------------------------------------------------------------

    /**
     * Check if two users are already friends
     */
    public function isFriend(int $userId, int $friendId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM friendships WHERE user_id = :user_id AND friend_id = :friend_id'
        );
        $stmt->execute([':user_id' => $userId, ':friend_id' => $friendId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Check if a friend request already exists between two users
     */
    public function requestExists(int $senderId, int $receiverId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM friend_requests 
             WHERE (sender_id = :s AND receiver_id = :r)
             OR (sender_id = :r2 AND receiver_id = :s2)'
        );
        $stmt->execute([
            ':s'  => $senderId,
            ':r'  => $receiverId,
            ':r2' => $receiverId,
            ':s2' => $senderId
        ]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Send a friend request
     */
    public function sendFriendRequest(int $senderId, int $receiverId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO friend_requests (sender_id, receiver_id, requested_at)
             VALUES (:sender_id, :receiver_id, :requested_at)'
        );
        $stmt->execute([
            ':sender_id'    => $senderId,
            ':receiver_id'  => $receiverId,
            ':requested_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Find a single friend request by its ID
     */
    public function findRequestById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.sender_id, r.receiver_id, r.requested_at, u.name AS sender_name
             FROM friend_requests r
             JOIN users u ON r.sender_id = u.id
             WHERE r.id = :id'
        );
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Accept a friend request — creates mutual friendship and deletes the request
     */
    public function acceptFriendRequest(int $requestId, int $senderId, int $receiverId): void
    {
        // Insert both directions for mutual friendship
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO friendships (user_id, friend_id) VALUES (:user_id, :friend_id)'
        );

        $stmt->execute([':user_id' => $senderId,   ':friend_id' => $receiverId]);
        $stmt->execute([':user_id' => $receiverId, ':friend_id' => $senderId]);

        // Delete the request now that it's been accepted
        $this->pdo->prepare('DELETE FROM friend_requests WHERE id = :id')
                  ->execute([':id' => $requestId]);
    }

    /**
     * Reject a friend request — simply deletes it
     */
    public function rejectFriendRequest(int $requestId): void
    {
        $this->pdo->prepare('DELETE FROM friend_requests WHERE id = :id')
                  ->execute([':id' => $requestId]);
    }

    // -------------------------------------------------------------------------
    // EXISTING METHODS (Unchanged)
    // -------------------------------------------------------------------------

    public function ensureUserExists(int $id, string $name, string $email): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if ($user) {
            return $user;
        }

        $sql = 'INSERT INTO users (id, name, email, eco_points, gained_today) 
                VALUES (:id, :name, :email, 1240, 80)';
        $insertStmt = $this->pdo->prepare($sql);
        $insertStmt->execute([
            'id'    => $id,
            'name'  => $name,
            'email' => $email
        ]);

        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getUserMetrics(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT eco_points, gained_today FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: ['eco_points' => 0, 'gained_today' => 0];
    }

    public function incrementPoints(int $id, int $points): void
    {
        $sql = 'UPDATE users SET eco_points = eco_points + :pts1, gained_today = gained_today + :pts2 WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'pts1' => $points,
            'pts2' => $points,
            'id'   => $id
        ]);
    }

 public function getLeaderboard(int $currentUserId): array
{
    $stmt = $this->pdo->query('SELECT id, name, eco_points FROM users ORDER BY eco_points DESC');
    $rows = $stmt->fetchAll();

    $leaderboard = [];
    $rank = 1;
    foreach ($rows as $row) {
        $isMe = ((int)$row['id'] === $currentUserId);
        $leaderboard[] = [
            'rank'            => $rank++,
            'name'            => $row['name'],
            'eco_points'      => (int)$row['eco_points'],
            'is_current_user' => $isMe
        ];
    }

    return $leaderboard;
}

    public function getFriends(int $userId): array
    {
        $sql = 'SELECT u.id, u.name, u.eco_points 
                FROM friendships f
                JOIN users u ON f.friend_id = u.id
                WHERE f.user_id = :user_id
                ORDER BY u.eco_points DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'id'         => (int)$row['id'],
                'name'       => $row['name'],
                'eco_points' => (int)$row['eco_points'],
                'avatar'     => strtolower(str_replace(' ', '_', $row['name'])) . '.jpg'
            ];
        }, $rows);
    }

    public function getPendingRequests(int $receiverId): array
    {
        $sql = 'SELECT r.id, u.name, r.requested_at 
                FROM friend_requests r
                JOIN users u ON r.sender_id = u.id
                WHERE r.receiver_id = :receiver_id
                ORDER BY r.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['receiver_id' => $receiverId]);
        return $stmt->fetchAll();
    }

    public function createUserDirect(array $data): void
    {
        $data['password_hash'] ??= '$2y$10$uSixnFrjcOkwKP.thvYswezo23rlMSXNEWSYT5uL3b3RIGacXn50e';
        $data['role'] ??= 'member';

        $sql = 'INSERT INTO users (id, name, email, password_hash, role, eco_points, gained_today) VALUES (:id, :name, :email, :password_hash, :role, :eco_points, :gained_today)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    public function establishFriendshipDirect(int $userId, int $friendId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO friendships (user_id, friend_id) VALUES (:user_id, :friend_id)');
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
    }

    public function createRequestDirect(array $data): void
    {
        $sql = 'INSERT INTO friend_requests (sender_id, receiver_id, requested_at) VALUES (:sender_id, :receiver_id, :requested_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    public function truncateUserTables(): void
    {
        $this->pdo->exec('DELETE FROM friend_requests');
        $this->pdo->exec('DELETE FROM friendships');
        $this->pdo->exec('DELETE FROM users');
    }
}
