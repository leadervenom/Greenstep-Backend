<?php
namespace App\Repositories;

use PDO;

final class ChallengeRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Fetch filtered challenges based on frontend tab parameters (all, joined, active, completed)
     */
    public function getChallengesByFilter(int $userId, string $filter): array
    {
        $sql = 'SELECT c.id, 
                       c.title, 
                       c.description, 
                       c.target_type,
                       c.created_by,
                       c.group_progress_percent,
                       IF(cm.user_id IS NOT NULL, 1, 0) AS has_joined
                FROM challenges c
                LEFT JOIN challenge_members cm ON c.id = cm.challenge_id AND cm.user_id = :user_id
                WHERE 1=1';

        if ($filter === 'joined') {
            $sql .= ' AND cm.user_id IS NOT NULL';
        } elseif ($filter === 'active') {
            $sql .= ' AND c.is_active = 1 AND c.is_completed = 0';
        } elseif ($filter === 'completed') {
            $sql .= ' AND c.is_completed = 1';
        }

        $sql .= ' ORDER BY c.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $results = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'id'                     => (int)$row['id'],
                'title'                  => $row['title'],
                'description'            => $row['description'],
                'target_type'            => $row['target_type'],
                'created_by'             => $row['created_by'] ? (int)$row['created_by'] : null,
                'filters'                => $row['has_joined'] ? ['all', 'joined', 'active'] : ['all', 'active'],
                'has_joined'             => (bool)$row['has_joined'],
                'group_progress_percent' => (float)$row['group_progress_percent']
            ];
        }, $results);
    }

    /**
     * Find a single challenge by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM challenges WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Join a challenge by inserting a row into the junction table
     */
    public function joinChallenge(int $challengeId, int $userId): bool
    {
        $checkStmt = $this->pdo->prepare(
            'SELECT 1 FROM challenge_members WHERE challenge_id = :challenge_id AND user_id = :user_id'
        );
        $checkStmt->execute(['challenge_id' => $challengeId, 'user_id' => $userId]);

        if ($checkStmt->fetch()) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO challenge_members (challenge_id, user_id) VALUES (:challenge_id, :user_id)'
        );
        return $stmt->execute(['challenge_id' => $challengeId, 'user_id' => $userId]);
    }

    /**
 * Check if a user has already joined a challenge
 */
public function isJoined(int $challengeId, int $userId): bool
{
    $stmt = $this->pdo->prepare(
        'SELECT 1 FROM challenge_members WHERE challenge_id = :challenge_id AND user_id = :user_id'
    );
    $stmt->execute([':challenge_id' => $challengeId, ':user_id' => $userId]);
    return (bool)$stmt->fetchColumn();
}

    /**
     * Create a new challenge, optionally stamping the creator's user ID
     */
    public function createChallenge(array $data): int
    {
        $sql = 'INSERT INTO challenges (title, description, target_type, group_progress_percent, created_by) 
                VALUES (:title, :description, :target_type, :group_progress_percent, :created_by)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title'                  => $data['title'],
            'description'            => $data['description'],
            'target_type'            => $data['target_type'],
            'group_progress_percent' => $data['group_progress_percent'] ?? 0.00,
            'created_by'             => $data['created_by'] ?? null
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Delete a challenge by ID
     */
    public function deleteChallenge(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM challenges WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Clear the junction table for a complete application data reset
     */
    public function clearUserChallenges(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM challenge_members WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}