<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class JsonBodyParser implements MiddlewareInterface 
{
    public function process(Request $request, Handler $handler): Response 
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (stripos($contentType, 'application/json') === 0) {
            $raw = (string)$request->getBody();
            $decoded = ($raw === '') ? [] : json_decode($raw, true);
            
            if (is_array($decoded)) {
                $request = $request->withParsedBody($decoded);
            }
        }
        return $handler->handle($request);
    }
}