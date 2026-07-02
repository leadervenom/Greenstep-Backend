<?php
namespace App\Repositories;

use PDO;

final class ActivityRepository
{
    /**
     * Inject the PDO instance directly into the constructor
     */
    public function __construct(private PDO $pdo) {}

    /**
     * Fetch all available activity types with their emission factors
     */
    public function getAllTypes(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM activity_types ORDER BY category, name');
        return $stmt->fetchAll();
    }

    /**
     * Find a single activity type by its unique ID
     */
    public function findTypeById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM activity_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get logs recorded by a specific user for a given calendar date
     */
    public function getLogsByDate(int $userId, string $date): array
    {
        $sql = 'SELECT l.id, 
                       DATE_FORMAT(l.logged_at, \'%h:%i %p\') AS time, 
                       t.name AS activity, 
                       l.amount, 
                       t.unit, 
                       l.emissions_kg 
                FROM activity_logs l
                JOIN activity_types t ON l.activity_type_id = t.id
                WHERE l.user_id = :user_id AND l.logged_date = :logged_date
                ORDER BY l.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'logged_date' => $date
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get aggregate history summary rows group by date for a user
     */
    public function getHistorySummary(int $userId): array
    {
        $sql = 'SELECT logged_date AS date, 
                       SUM(emissions_kg) AS total_emissions_kg, 
                       COUNT(id) AS logs_count 
                FROM activity_logs 
                WHERE user_id = :user_id 
                GROUP BY logged_date 
                ORDER BY logged_date DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Store a new carbon footprint activity log securely
     */
    public function createLog(array $data): int
    {
        $sql = 'INSERT INTO activity_logs (user_id, activity_type_id, amount, emissions_kg, logged_date, logged_at) 
                VALUES (:user_id, :activity_type_id, :amount, :emissions_kg, :logged_date, :logged_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id'          => $data['user_id'],
            'activity_type_id' => $data['activity_type_id'],
            'amount'           => $data['amount'],
            'emissions_kg'     => $data['emissions_kg'],
            'logged_date'      => $data['logged_date'],
            'logged_at'        => $data['logged_at']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Clears all log records for demo environment resets
     */
    public function clearUserLogs(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM activity_logs WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}