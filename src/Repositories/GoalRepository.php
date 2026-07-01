<?php
namespace App\Repositories;

use PDO;

final class GoalRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Fetch the active carbon footprint target details for a specific user
     */
    public function getActiveGoal(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM goals WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) return null;

        return [
            'id'                          => (int)$row['id'],
            'title'                       => $row['title'],
            'target_to_reduce_kg'         => (float)$row['target_to_reduce_kg'],
            'duration'                    => $row['duration'],
            'start_date'                  => $row['start_date'],
            'emissions_reduced_so_far_kg' => (float)$row['emissions_reduced_so_far_kg']
        ];
    }

    /**
     * Create a new goal for a user via the API
     */
    public function createGoal(array $data): int
    {
        $sql = 'INSERT INTO goals (user_id, title, target_to_reduce_kg, duration, start_date, emissions_reduced_so_far_kg)
                VALUES (:user_id, :title, :target_to_reduce_kg, :duration, :start_date, 0.00)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id'              => $data['user_id'],
            ':title'                => $data['title'],
            ':target_to_reduce_kg'  => $data['target_to_reduce_kg'],
            ':duration'             => $data['duration'],
            ':start_date'           => $data['start_date']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Delete the current active goal for a user before setting a new one
     */
    public function deleteActiveGoal(int $userId): void
    {
        $this->pdo->prepare('DELETE FROM goals WHERE user_id = :user_id')
                  ->execute([':user_id' => $userId]);
    }

    /**
     * Fetch all uploaded achievement photo milestones for a specific user
     */
    public function getUserPhotos(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, image_url, achievement, uploaded_on 
             FROM eco_photos 
             WHERE user_id = :user_id 
             ORDER BY id DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $results = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'id'          => (int)$row['id'],
                'image_url'   => $row['image_url'],
                'achievement' => $row['achievement'],
                'uploaded_on' => $row['uploaded_on']
            ];
        }, $results);
    }

    /**
     * Add a brand new verified eco photo upload entry to the timeline
     */
    public function createPhoto(array $data): int
    {
        $sql = 'INSERT INTO eco_photos (user_id, image_url, achievement, uploaded_on) 
                VALUES (:user_id, :image_url, :achievement, :uploaded_on)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id'     => $data['user_id'],
            'image_url'   => $data['image_url'],
            'achievement' => $data['achievement'],
            'uploaded_on' => $data['uploaded_on']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Seed initial performance goal row during database reset
     */
    public function createGoalDirect(array $data): void
    {
        $sql = 'INSERT INTO goals (user_id, title, target_to_reduce_kg, duration, start_date, emissions_reduced_so_far_kg) 
                VALUES (:user_id, :title, :target_to_reduce_kg, :duration, :start_date, :emissions_reduced_so_far_kg)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
    }

    /**
     * Update progress numbers for the current active goal
     */
    public function updateGoalProgress(int $userId, float $reducedKg): void
    {
        $sql = 'UPDATE goals SET emissions_reduced_so_far_kg = emissions_reduced_so_far_kg + :reduced_kg WHERE user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'reduced_kg' => $reducedKg,
            'user_id'    => $userId
        ]);
    }

    /**
     * Clear user goals and photo entries during application reset
     */
    public function truncateGoalAndPhotoTables(): void
    {
        $this->pdo->exec('DELETE FROM eco_photos');
        $this->pdo->exec('DELETE FROM goals');
    }
}