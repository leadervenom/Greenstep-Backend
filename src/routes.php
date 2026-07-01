<?php
use Slim\App;
use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\FriendController;
use App\Controllers\GoalController;
use App\Controllers\EcoPhotoController;
use App\Repositories\ActivityRepository;
use App\Repositories\ChallengeRepository;
use App\Repositories\TipRepository;
use App\Repositories\UserRepository;
use App\Repositories\GoalRepository;
use App\Auth\JwtService;
use App\Middleware\AuthMiddleware;
use App\Database;

return function (App $app) {

    // -------------------------------------------------------------------------
    // 1. Build shared dependencies
    // -------------------------------------------------------------------------
    $pdo  = Database::get();
    $jwt  = new JwtService();
    $auth = new AuthMiddleware($jwt);

    $activityRepo  = new ActivityRepository($pdo);
    $challengeRepo = new ChallengeRepository($pdo);
    $tipRepo       = new TipRepository($pdo);
    $userRepo      = new UserRepository($pdo);
    $goalRepo      = new GoalRepository($pdo);

    $activityCtrl = new ActivityController(
        $activityRepo,
        $challengeRepo,
        $tipRepo,
        $userRepo,
        $goalRepo
    );

    $authCtrl     = new AuthController($userRepo, $jwt);
    $friendCtrl   = new FriendController($userRepo);
    $goalCtrl     = new GoalController($goalRepo);
    $ecoPhotoCtrl = new EcoPhotoController($goalRepo, $userRepo);

    // -------------------------------------------------------------------------
    // 2. Public auth routes
    // -------------------------------------------------------------------------
    $app->post('/auth/register', [$authCtrl, 'register']);
    $app->post('/auth/login',    [$authCtrl, 'login']);

    // -------------------------------------------------------------------------
    // 3. Public read-only routes (no token required)
    // -------------------------------------------------------------------------
    $app->get('/api/activities/types', [$activityCtrl, 'getActivityTypes']);
    $app->get('/api/tips',             [$activityCtrl, 'getTips']);

    // -------------------------------------------------------------------------
    // 4. Protected route — requires valid JWT
    // -------------------------------------------------------------------------
    $app->get('/auth/me', [$authCtrl, 'me'])->add($auth);

    // -------------------------------------------------------------------------
    // 5. Protected routes — requires valid JWT
    // -------------------------------------------------------------------------
    $app->group('/api', function ($g) use ($activityCtrl, $friendCtrl, $goalCtrl, $ecoPhotoCtrl) {

        /* --- Dashboard --- */
        $g->get('/dashboard', [$activityCtrl, 'getDashboardSummary']);

        /* --- Activities --- */
        $g->get('/activities/today',   [$activityCtrl, 'getTodayLogs']);
        $g->get('/activities/history', [$activityCtrl, 'getHistory']);
        $g->post('/activities/log',    [$activityCtrl, 'logActivity']);

        /* --- Challenges --- */
        $g->get('/challenges',            [$activityCtrl, 'getChallenges']);
        $g->post('/challenges',           [$activityCtrl, 'createChallenge']);
        $g->delete('/challenges/{id}',    [$activityCtrl, 'deleteChallenge']);
        $g->post('/challenges/{id}/join', [$activityCtrl, 'joinChallenge']);

        /* --- Friends --- */
        $g->get('/friends',              [$friendCtrl, 'getFriends']);
        $g->get('/friends/requests',     [$friendCtrl, 'getPendingRequests']);
        $g->post('/friends/request',     [$friendCtrl, 'sendRequest']);
        $g->post('/friends/accept/{id}', [$friendCtrl, 'acceptRequest']);
        $g->post('/friends/reject/{id}', [$friendCtrl, 'rejectRequest']);

        /* --- Leaderboard --- */
        $g->get('/leaderboard', [$activityCtrl, 'getLeaderboard']);

        /* --- Goals --- */
        $g->get('/goals',  [$goalCtrl, 'getGoal']);
        $g->post('/goals', [$goalCtrl, 'setGoal']);

        /* --- Eco Photos --- */
        $g->get('/photos',  [$ecoPhotoCtrl, 'getPhotos']);
        $g->post('/photos', [$ecoPhotoCtrl, 'uploadPhoto']);

        /* --- System --- */
        $g->post('/reset', [$activityCtrl, 'reset']);

    })->add($auth);
};