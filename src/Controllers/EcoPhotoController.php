<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\GoalRepository;
use App\Repositories\UserRepository;

final class EcoPhotoController
{
    public function __construct(
        private GoalRepository $goalRepo,
        private UserRepository $userRepo
    ) {}

    /* ---------- GET /api/photos ---------- */
    public function getPhotos(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        $photos = $this->goalRepo->getUserPhotos($userId);

        // Calculate bonus points (40 points per photo)
        $totalPhotos      = count($photos);
        $totalBonusPoints = $totalPhotos * 40;

        return $this->json($res, [
            'user_id'       => $userId,
            'total_photos'  => $totalPhotos,
            'bonus_points'  => [
                'points_per_photo' => 40,
                'total_earned'     => $totalBonusPoints
            ],
            'my_eco_photos' => $photos
        ]);
    }

    /* ---------- POST /api/photos ---------- */
    public function uploadPhoto(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);
        $body   = (array)($req->getParsedBody() ?? []);

        // Validate required fields
        $errors = [];
        if (empty($body['image_url']))
            $errors['image_url'] = 'image_url is required';
        if (empty($body['achievement']))
            $errors['achievement'] = 'achievement is required';

        if ($errors) {
            return $this->json($res, ['errors' => $errors], 400);
        }

        $newId = $this->goalRepo->createPhoto([
            'user_id'     => $userId,
            'image_url'   => trim($body['image_url']),
            'achievement' => trim($body['achievement']),
            'uploaded_on' => date('Y-m-d')
        ]);

        $this->userRepo->incrementPoints($userId, 40);

        return $this->json($res, [
            'message'    => 'Eco photo uploaded successfully',
            'eco_points' => '+40 points awarded',
            'photo'      => [
                'id'          => $newId,
                'user_id'     => $userId,
                'image_url'   => trim($body['image_url']),
                'achievement' => trim($body['achievement']),
                'uploaded_on' => date('Y-m-d')
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