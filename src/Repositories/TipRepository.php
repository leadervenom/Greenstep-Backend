<?php
namespace App\Repositories;

use PDO;

final class TipRepository
{
    /**
     * Inject the PDO instance directly into the constructor
     */
    public function __construct(private PDO $pdo) {}

    /**
     * Fetch a specific tip by its ID (used for Dashboard stitching)
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tips WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) return null;

        return [
            'id' => (int)$row['id'],
            'labels' => ['All Tips', $row['category']],
            'title' => $row['title'],
            'body' => $row['body']
        ];
    }

    /**
     * Fetch all tips or tips filtered by category
     */
    public function getTipsByCategory(string $category): array
    {
        if (empty($category) || strtolower($category) === 'all' || strtolower($category) === 'all tips') {
            $stmt = $this->pdo->query('SELECT * FROM tips ORDER BY id ASC');
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM tips WHERE category = :category ORDER BY id ASC');
            $stmt->execute(['category' => $category]);
        }

        $results = $stmt->fetchAll();

        return array_map(function($row) {
            return [
                'id' => (int)$row['id'],
                'labels' => ['All Tips', $row['category']],
                'title' => $row['title'],
                'body' => $row['body']
            ];
        }, $results);
    }

    /**
     * Seed initial tips into the database table during environment reset
     */
    public function createTip(array $data): int
    {
        $sql = 'INSERT INTO tips (title, body, category) VALUES (:title, :body, :category)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title'    => $data['title'],
            'body'     => $data['body'],
            'category' => $data['category']
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Clear all tip entries to prepare for clean baseline seeding
     */
    public function truncateTips(): void
    {
        $this->pdo->exec('DELETE FROM tips');
        $this->pdo->exec('ALTER TABLE tips AUTO_INCREMENT = 1');
    }
}