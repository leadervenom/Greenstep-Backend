<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\GoalRepository;

final class GoalController
{
    public function __construct(private GoalRepository $goalRepo) {}

    /* ---------- GET /api/goals ---------- */
    public function getGoal(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        $goal = $this->goalRepo->getActiveGoal($userId);

        if (!$goal) {
            return $this->json($res, [
                'goal'    => null,
                'message' => 'No active goal set yet'
            ]);
        }

        // Calculate progress percentage
        $progressPercent = $goal['target_to_reduce_kg'] > 0
            ? round(($goal['emissions_reduced_so_far_kg'] / $goal['target_to_reduce_kg']) * 100, 1)
            : 0;

        return $this->json($res, [
            'current_goal' => [
                'id'                  => $goal['id'],
                'title'               => $goal['title'],
                'target_to_reduce_kg' => $goal['target_to_reduce_kg'],
                'duration'            => $goal['duration'],
                'start_date'          => $goal['start_date'],
            ],
            'progress' => [
                'emissions_reduced_so_far_kg' => $goal['emissions_reduced_so_far_kg'],
                'target_to_reduce_kg'         => $goal['target_to_reduce_kg'],
                'progress_percent'            => $progressPercent,
                'is_completed'                => $progressPercent >= 100
            ]
        ]);
    }

    /* ---------- POST /api/goals ---------- */
    public function setGoal(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);
        $body   = (array)($req->getParsedBody() ?? []);

        // Validate required fields
        $errors = [];
        if (empty($body['title']))
            $errors['title'] = 'title is required';
        if (empty($body['target_to_reduce_kg']) || (float)$body['target_to_reduce_kg'] <= 0)
            $errors['target_to_reduce_kg'] = 'target_to_reduce_kg must be a positive number';
        if (empty($body['duration']))
            $errors['duration'] = 'duration is required';
        if (empty($body['start_date']))
            $errors['start_date'] = 'start_date is required';

        if ($errors) {
            return $this->json($res, ['errors' => $errors], 400);
        }

        // Delete existing goal before setting a new one
        $this->goalRepo->deleteActiveGoal($userId);

        $newId = $this->goalRepo->createGoal([
            'user_id'             => $userId,
            'title'               => trim($body['title']),
            'target_to_reduce_kg' => (float)$body['target_to_reduce_kg'],
            'duration'            => trim($body['duration']),
            'start_date'          => $body['start_date']
        ]);

        return $this->json($res, [
            'message' => 'Goal set successfully',
            'goal'    => [
                'id'                          => $newId,
                'user_id'                     => $userId,
                'title'                       => trim($body['title']),
                'target_to_reduce_kg'         => (float)$body['target_to_reduce_kg'],
                'duration'                    => trim($body['duration']),
                'start_date'                  => $body['start_date'],
                'emissions_reduced_so_far_kg' => 0.00
            ]
        ], 201);
    }

    /* ---------- Helper Utilities ---------- */
    private function json(Response $res, mixed $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}