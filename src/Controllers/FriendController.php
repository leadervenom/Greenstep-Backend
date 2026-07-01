<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\UserRepository;

final class FriendController
{
    public function __construct(private UserRepository $userRepo) {}

    /* ---------- GET /api/friends ---------- */
    public function getFriends(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        $friends = $this->userRepo->getFriends($userId);

        return $this->json($res, [
            'user_id' => $userId,
            'total'   => count($friends),
            'friends' => $friends
        ]);
    }

    /* ---------- GET /api/friends/requests ---------- */
    public function getPendingRequests(Request $req, Response $res): Response
    {
        $auth   = (array)$req->getAttribute('auth', []);
        $userId = (int)($auth['sub'] ?? 0);

        $requests = $this->userRepo->getPendingRequests($userId);

        return $this->json($res, [
            'user_id'          => $userId,
            'total'            => count($requests),
            'pending_requests' => $requests
        ]);
    }

    /* ---------- POST /api/friends/request ---------- */
    public function sendRequest(Request $req, Response $res): Response
    {
        $auth     = (array)$req->getAttribute('auth', []);
        $senderId = (int)($auth['sub'] ?? 0);
        $body     = (array)($req->getParsedBody() ?? []);

        if (empty($body['receiver_id'])) {
            return $this->json($res, ['error' => 'receiver_id is required'], 400);
        }

        $receiverId = (int)$body['receiver_id'];

        // Cannot send a request to yourself
        if ($senderId === $receiverId) {
            return $this->json($res, ['error' => 'You cannot send a friend request to yourself'], 400);
        }

        // Check if receiver exists
        $receiver = $this->userRepo->findById($receiverId);
        if (!$receiver) {
            return $this->json($res, ['error' => 'User not found'], 404);
        }

        // Check if already friends
        if ($this->userRepo->isFriend($senderId, $receiverId)) {
            return $this->json($res, ['error' => 'You are already friends with this user'], 409);
        }

        // Check if request already exists
        if ($this->userRepo->requestExists($senderId, $receiverId)) {
            return $this->json($res, ['error' => 'Friend request already sent'], 409);
        }

        $this->userRepo->sendFriendRequest($senderId, $receiverId);

        return $this->json($res, [
            'message'     => 'Friend request sent successfully',
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId
        ], 201);
    }

    /* ---------- POST /api/friends/accept/{id} ---------- */
    public function acceptRequest(Request $req, Response $res, array $args): Response
    {
        $auth       = (array)$req->getAttribute('auth', []);
        $receiverId = (int)($auth['sub'] ?? 0);
        $requestId  = (int)($args['id'] ?? 0);

        // Fetch the request and verify it belongs to this user
        $request = $this->userRepo->findRequestById($requestId);

        if (!$request) {
            return $this->json($res, ['error' => 'Friend request not found'], 404);
        }

        if ((int)$request['receiver_id'] !== $receiverId) {
            return $this->json($res, ['error' => 'You are not authorised to accept this request'], 403);
        }

        $senderId = (int)$request['sender_id'];

        // Create mutual friendship and delete the request
        $this->userRepo->acceptFriendRequest($requestId, $senderId, $receiverId);

        return $this->json($res, [
            'message'   => 'Friend request accepted',
            'friend_id' => $senderId
        ]);
    }

    /* ---------- POST /api/friends/reject/{id} ---------- */
    public function rejectRequest(Request $req, Response $res, array $args): Response
    {
        $auth       = (array)$req->getAttribute('auth', []);
        $receiverId = (int)($auth['sub'] ?? 0);
        $requestId  = (int)($args['id'] ?? 0);

        // Fetch the request and verify it belongs to this user
        $request = $this->userRepo->findRequestById($requestId);

        if (!$request) {
            return $this->json($res, ['error' => 'Friend request not found'], 404);
        }

        if ((int)$request['receiver_id'] !== $receiverId) {
            return $this->json($res, ['error' => 'You are not authorised to reject this request'], 403);
        }

        $this->userRepo->rejectFriendRequest($requestId);

        return $this->json($res, [
            'message' => 'Friend request rejected'
        ]);
    }

    /* ---------- Helper Utilities ---------- */
    private function json(Response $res, mixed $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json; charset=utf-8')->withStatus($status);
    }
}