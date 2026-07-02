<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class Cors implements MiddlewareInterface 
{
    public function process(Request $request, Handler $handler): Response 
    {
        // Handle preflight OPTIONS requests immediately
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response(204);
            $origin = $request->getHeaderLine('Origin');
            return $this->withCorsHeaders($response, $origin);
        }

        // Process request pipeline and add CORS headers to the response
        $response = $handler->handle($request);
        $origin = $request->getHeaderLine('Origin');
        return $this->withCorsHeaders($response, $origin);
    }

    private function withCorsHeaders(Response $response, ?string $origin = null): Response 
    {
        $allowOrigin = $origin ?: '*';

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Vary', 'Origin');

        if (!empty($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}