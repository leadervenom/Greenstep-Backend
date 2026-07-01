<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\ActivityRepository;
use App\Repositories\ChallengeRepository;
use App\Repositories\TipRepository;
use App\Repositories\UserRepository;
use App\Repositories\GoalRepository;

final class ActivityController
{
    public function __construct(
        private ActivityRepository  $activityRepo,
        private ChallengeRepository $challengeRepo,
        private TipRepository       $tipRepo,
        private UserRepository      $userRepo,
        private GoalRepository      $goalRepo
    ) {}

    /* ---------- GET /api/dashboard ---------- */
    public function getDashboardSummary(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 1);

        $userMetrics = $this->userRepo->getUserMetrics($userId);
        $logsSummary = $this->activityRepo->getHistorySummary($userId);

        $weeklyTotal = 0.0;
        foreach ($logsSummary as $historyRow) {
            $weeklyTotal += (float)$historyRow['total_emissions_kg'];
        }

        $currentDate    = date('Y-m-d');
        $todayLogs      = $this->activityRepo->getLogsByDate($userId, $currentDate);
        $todayEmissions = 0.0;
        $breakdown      = ['transport' => 0.0, 'food' => 0.0, 'energy' => 0.0, 'waste' => 0.0];

        foreach ($todayLogs as $log) {
            $emissions      = (float)$log['emissions_kg'];
            $todayEmissions += $emissions;
            $activityName   = strtolower($log['activity']);

            if (str_contains($activityName, 'car') || str_contains($activityName, 'train') || str_contains($activityName, 'bus') || str_contains($activityName, 'flight')) {
                $breakdown['transport'] += $emissions;
            } elseif (str_contains($activityName, 'meal')) {
                $breakdown['food'] += $emissions;
            } elseif (str_contains($activityName, 'electricity')) {
                $breakdown['energy'] += $emissions;
            } elseif (str_contains($activityName, 'recycling')) {
                $breakdown['waste'] += $emissions;
            }
        }

        $dashboardPayload = [
            'panels' => [
                'today_emissions_kg' => round($todayEmissions, 2),
                'weekly_total_kg'    => round($weeklyTotal, 2),
                'monthly_average_kg' => 385.00,
                'eco_points'         => [
                    'current_total' => (int)$userMetrics['eco_points'],
                    'gained_today'  => (int)$userMetrics['gained_today']
                ]
            ],
            'charts' => [
                'today_breakdown' => [
                    'transport' => round($breakdown['transport'], 2),
                    'food'      => round($breakdown['food'], 2),
                    'energy'    => round($breakdown['energy'], 2)
                ],
                'weekly_history_graph' => [
                    ['day' => 'Monday',    'emissions_kg' => 15.2],
                    ['day' => 'Tuesday',   'emissions_kg' => 12.4],
                    ['day' => 'Wednesday', 'emissions_kg' => 18.1],
                    ['day' => 'Thursday',  'emissions_kg' => round($todayEmissions, 2)],
                    ['day' => 'Friday',    'emissions_kg' => 0.0],
                    ['day' => 'Saturday',  'emissions_kg' => 0.0],
                    ['day' => 'Sunday',    'emissions_kg' => 0.0]
                ]
            ],
            'todays_tip'       => $this->tipRepo->findById(2),
            'active_challenge' => $this->challengeRepo->getChallengesByFilter($userId, 'all')[0] ?? null
        ];

        return $this->json($res, $dashboardPayload);
    }

    /* ---------- GET /api/activities/types ---------- */
    public function getActivityTypes(Request $req, Response $res): Response
    {
        return $this->json($res, $this->activityRepo->getAllTypes());
    }

    /* ---------- POST /api/activities/log ---------- */
    public function logActivity(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);
        $body   = (array)($req->getParsedBody() ?? []);

        if (empty($body['activity_type_id']) || empty($body['date'])) {
            return $this->json($res, ['error' => 'activity_type_id and date are required fields'], 400);
        }

        $typeId       = (int)$body['activity_type_id'];
        $amount       = isset($body['amount']) ? (float)$body['amount'] : 1.0;
        $activityType = $this->activityRepo->findTypeById($typeId);

        if (!$activityType) {
            return $this->json($res, ['error' => 'Invalid activity type selected'], 404);
        }

        $calculatedEmissions = (float)$activityType['kg_co2_per_unit'] * $amount;

        $newLogId = $this->activityRepo->createLog([
            'user_id'          => $userId,
            'activity_type_id' => $typeId,
            'amount'           => $amount,
            'emissions_kg'     => round($calculatedEmissions, 2),
            'logged_date'      => date('Y-m-d', strtotime($body['date'])),
            'logged_at'        => date('Y-m-d H:i:s')
        ]);

        $this->userRepo->incrementPoints($userId, 15);

        return $this->json($res, [
            'message'     => 'Activity recorded successfully!',
            'logged_item' => [
                'id'           => $newLogId,
                'time'         => date('h:i A'),
                'activity'     => $activityType['name'],
                'amount'       => $amount,
                'unit'         => $activityType['unit'],
                'emissions_kg' => round($calculatedEmissions, 2)
            ]
        ], 201);
    }

    /* ---------- GET /api/activities/today ---------- */
    public function getTodayLogs(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        return $this->json($res, $this->activityRepo->getLogsByDate($userId, date('Y-m-d')));
    }

    /* ---------- GET /api/activities/history ---------- */
    public function getHistory(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        return $this->json($res, $this->activityRepo->getHistorySummary($userId));
    }

    /* ---------- GET /api/leaderboard ---------- */
    public function getLeaderboard(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        return $this->json($res, [
            'total'       => count($this->userRepo->getLeaderboard($userId)),
            'leaderboard' => $this->userRepo->getLeaderboard($userId)
        ]);
    }

    /* ---------- GET /api/challenges ---------- */
    public function getChallenges(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);
        $filter = !empty($req->getQueryParams()['filter'])
            ? strtolower(trim((string)$req->getQueryParams()['filter']))
            : 'all';

        return $this->json($res, $this->challengeRepo->getChallengesByFilter($userId, $filter));
    }

    /* ---------- POST /api/challenges ---------- */
    public function createChallenge(Request $req, Response $res): Response
    {
        $auth      = (array)$req->getAttribute('auth', []);
        $createdBy = (int)($auth['sub'] ?? 0) ?: null;
        $body      = (array)($req->getParsedBody() ?? []);

        if (empty($body['title']) || empty($body['description']) || empty($body['target_type'])) {
            return $this->json($res, ['error' => 'title, description, and target_type are mandatory fields'], 400);
        }

        $newId = $this->challengeRepo->createChallenge([
            'title'                  => trim($body['title']),
            'description'            => trim($body['description']),
            'target_type'            => trim($body['target_type']),
            'group_progress_percent' => 0.00,
            'created_by'             => $createdBy
        ]);

        return $this->json($res, [
            'message'   => 'Custom challenge posted successfully',
            'challenge' => [
                'id'                     => $newId,
                'title'                  => trim($body['title']),
                'description'            => trim($body['description']),
                'target_type'            => trim($body['target_type']),
                'created_by'             => $createdBy,
                'filters'                => ['all', 'active'],
                'has_joined'             => false,
                'group_progress_percent' => 0.0
            ]
        ], 201);
    }

    /* ---------- DELETE /api/challenges/{id} ---------- */
    public function deleteChallenge(Request $req, Response $res, array $args): Response
    {
        $auth        = (array)$req->getAttribute('auth', []);
        $currentUser = (int)($auth['sub'] ?? 0);
        $challengeId = (int)($args['id'] ?? 0);

        $challenge = $this->challengeRepo->findById($challengeId);
        if (!$challenge) {
            return $this->json($res, ['error' => 'Challenge not found'], 404);
        }

        if ((int)$challenge['created_by'] !== $currentUser) {
            return $this->json($res, ['error' => 'Only the challenge creator can delete this challenge'], 403);
        }

        $this->challengeRepo->deleteChallenge($challengeId);

        return $this->json($res, ['message' => 'Challenge deleted successfully']);
    }


    /* ---------- POST /api/challenges/{id}/join ---------- */
public function joinChallenge(Request $req, Response $res, array $args): Response
{
    $auth        = (array)$req->getAttribute('auth', []);
    $userId      = (int)($auth['sub'] ?? 0);
    $challengeId = (int)($args['id'] ?? 0);

    // Check challenge exists
    $challenge = $this->challengeRepo->findById($challengeId);
    if (!$challenge) {
        return $this->json($res, ['error' => 'Challenge not found'], 404);
    }

    // Check if already joined
    $alreadyJoined = $this->challengeRepo->isJoined($challengeId, $userId);
    if ($alreadyJoined) {
        return $this->json($res, ['error' => 'You have already joined this challenge'], 409);
    }

    $this->challengeRepo->joinChallenge($challengeId, $userId);

    return $this->json($res, [
        'message'      => 'Successfully joined the challenge',
        'challenge_id' => $challengeId,
        'user_id'      => $userId
    ]);
}

    /* ---------- GET /api/tips ---------- */
    public function getTips(Request $req, Response $res): Response
    {
        $category = !empty($req->getQueryParams()['category'])
            ? trim((string)$req->getQueryParams()['category'])
            : 'all';

        return $this->json($res, $this->tipRepo->getTipsByCategory($category));
    }

    /* ---------- POST /api/reset ---------- */
    public function reset(Request $req, Response $res): Response
    {
        $this->activityRepo->clearUserLogs(1);
        $this->challengeRepo->clearUserChallenges(1);
        $this->goalRepo->truncateGoalAndPhotoTables();
        $this->tipRepo->truncateTips();
        $this->userRepo->truncateUserTables();

        $dbReflection = new \ReflectionClass($this->activityRepo);
        $pdoProperty  = $dbReflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $rawPdo = $pdoProperty->getValue($this->activityRepo);

        $rawPdo->exec('DELETE FROM activity_logs');
        $rawPdo->exec('DELETE FROM activity_types');
        $rawPdo->exec('ALTER TABLE activity_types AUTO_INCREMENT = 1');

        $activityTypesSeed = [
            ['id' => 1, 'category' => 'Transport', 'name' => 'Car (Petrol)',        'unit' => 'km',    'kg_co2_per_unit' => 0.21,  'info' => 'Average medium-sized gasoline passenger vehicle'],
            ['id' => 2, 'category' => 'Transport', 'name' => 'Electric Vehicle',    'unit' => 'km',    'kg_co2_per_unit' => 0.05,  'info' => 'Based on regional electricity grid mix cleaner values'],
            ['id' => 3, 'category' => 'Transport', 'name' => 'Train',               'unit' => 'km',    'kg_co2_per_unit' => 0.04,  'info' => 'National transit electric and diesel passenger average'],
            ['id' => 4, 'category' => 'Transport', 'name' => 'Bus',                 'unit' => 'km',    'kg_co2_per_unit' => 0.09,  'info' => 'Standard city bus network route occupancy factor'],
            ['id' => 5, 'category' => 'Food',      'name' => 'Meat-Based Meal',     'unit' => 'meals', 'kg_co2_per_unit' => 6.00,  'info' => 'High carbon footprint featuring beef, lamb, or pork ingredients'],
            ['id' => 6, 'category' => 'Food',      'name' => 'Plant-Based Meal',    'unit' => 'meals', 'kg_co2_per_unit' => 0.70,  'info' => 'Low footprint vegan or vegetarian meal configuration'],
            ['id' => 7, 'category' => 'Energy',    'name' => 'Electricity Usage',   'unit' => 'kWh',   'kg_co2_per_unit' => 0.50,  'info' => 'Per kilowatt-hour consumed from fossil grid generation'],
            ['id' => 8, 'category' => 'Waste',     'name' => 'Recycling Action',    'unit' => 'items', 'kg_co2_per_unit' => -0.15, 'info' => 'Negative emission values representing lifecycle credits earned'],
            ['id' => 9, 'category' => 'Transport', 'name' => 'Flight (Short Haul)', 'unit' => 'km',    'kg_co2_per_unit' => 0.25,  'info' => 'Aviation tracking multiplier for intra-state segments']
        ];

        foreach ($activityTypesSeed as $type) {
            $stmt = $rawPdo->prepare(
                'INSERT INTO activity_types (id, category, name, unit, kg_co2_per_unit, info)
                 VALUES (:id, :category, :name, :unit, :kg_co2_per_unit, :info)'
            );
            $stmt->execute($type);
        }

        $seed = require __DIR__ . '/../Data/data.php';

        $this->userRepo->createUserDirect(['id' => 1,   'name' => 'You (GreenRunner)', 'email' => 'runner@greenstep.org', 'eco_points' => 1240, 'gained_today' => 80]);
        $this->userRepo->createUserDirect(['id' => 201, 'name' => 'Sarah Connor',      'email' => 'sarah@sky.net',        'eco_points' => 1420, 'gained_today' => 0]);
        $this->userRepo->createUserDirect(['id' => 202, 'name' => 'Alex Mercer',       'email' => 'alex@gentek.org',      'eco_points' => 1100, 'gained_today' => 0]);
        $this->userRepo->createUserDirect(['id' => 203, 'name' => 'Emma Watson',       'email' => 'emma@unwomen.org',     'eco_points' => 1680, 'gained_today' => 0]);

        $this->userRepo->establishFriendshipDirect(1, 201);
        $this->userRepo->establishFriendshipDirect(1, 202);
        $this->userRepo->establishFriendshipDirect(1, 203);
        $this->userRepo->createRequestDirect(['sender_id' => 201, 'receiver_id' => 1, 'requested_at' => '2 hours ago']);

        foreach ($seed['tip_library'] as $tip) {
            $this->tipRepo->createTip([
                'title'    => $tip['title'],
                'body'     => $tip['body'],
                'category' => str_replace('All Tips', '', $tip['labels'][1] ?? 'General')
            ]);
        }

        foreach ($seed['challenges'] as $c) {
            $newId = $this->challengeRepo->createChallenge([
                'title'                  => $c['title'],
                'description'            => $c['description'],
                'target_type'            => $c['target_type'],
                'group_progress_percent' => $c['group_progress_percent'],
                'created_by'             => null
            ]);
            if ($c['has_joined']) {
                $this->challengeRepo->joinChallenge($newId, 1);
            }
        }

        $this->goalRepo->createGoalDirect([
            'user_id'                     => 1,
            'title'                       => $seed['my_goal_page']['current_goal']['title'],
            'target_to_reduce_kg'         => $seed['my_goal_page']['current_goal']['target_to_reduce_kg'],
            'duration'                    => $seed['my_goal_page']['current_goal']['duration'],
            'start_date'                  => $seed['my_goal_page']['current_goal']['start_date'],
            'emissions_reduced_so_far_kg' => $seed['my_goal_page']['progress']['emissions_reduced_so_far_kg']
        ]);

        foreach ($seed['eco_photos_page']['my_eco_photos'] as $photo) {
            $this->goalRepo->createPhoto([
                'user_id'     => 1,
                'image_url'   => $photo['image_url'],
                'achievement' => $photo['achievement'],
                'uploaded_on' => $photo['uploaded_on']
            ]);
        }

        $freshDbTypes   = $this->activityRepo->getAllTypes();
        $typeMapping    = [];
        foreach ($freshDbTypes as $dbType) {
            $typeMapping[strtolower($dbType['name'])] = (int)$dbType['id'];
        }
        $fallbackTypeId = (int)$freshDbTypes[0]['id'];

        foreach ($seed['today_log_record'] as $log) {
            $logNameLower  = strtolower($log['activity']);
            $matchedTypeId = $fallbackTypeId;

            foreach ($typeMapping as $typeName => $realId) {
                if (str_contains($logNameLower, str_replace(['(', ')'], '', $typeName)) || str_contains($typeName, $logNameLower)) {
                    $matchedTypeId = $realId;
                    break;
                }
            }

            $this->activityRepo->createLog([
                'user_id'          => 1,
                'activity_type_id' => $matchedTypeId,
                'amount'           => $log['amount'],
                'emissions_kg'     => $log['emissions_kg'],
                'logged_date'      => date('Y-m-d'),
                'logged_at'        => date('Y-m-d H:i:s')
            ]);
        }

        return $this->json($res, ['message' => 'Application metrics reset to baseline relational tables successfully']);
    }

    /* ---------- Helper Utilities ---------- */
    private function json(Response $res, mixed $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $res->withHeader('Access-Control-Allow-Origin', '*');
        return $res->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}