<?php

namespace App\Middlewares;

use App\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifySignatureMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly Config $config
    ){}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $queryParams = $request->getQueryParams();
        $originalSignature = $queryParams['signature'] ?? '';
        $expirationDate = (int) ($queryParams['expiration'] ?? 0);

        unset($queryParams['signature']);

        $url = (string) $uri->withQuery(http_build_query($queryParams));
        $signature = hash_hmac('sha256', $url, $this->config->get('app_key'));

        if($expirationDate < time() || ! hash_equals($signature, $originalSignature)){
            throw new \RuntimeException('La validation de la signature a échoué');
        }

        return $handler->handle($request);
    }
}