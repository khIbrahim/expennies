<?php

namespace App\Middlewares;

use App\Config;
use App\IpResolver;
use App\Services\RequestService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Slim\Routing\RouteContext;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly CacheInterface           $cache,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly IpResolver               $ipResolver,
        private readonly Config                   $config,
        private readonly RateLimiterFactory       $rateLimiterFactory
    ){}

    /**
     * @throws InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->ipResolver->getClientIp($request, $this->config->get('trustedProxies'));
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $rateLimiter = $this->rateLimiterFactory->create($route->getName() . '_' . $clientIp);

        if ($rateLimiter->consume()->isAccepted() === false){
            return $this->responseFactory->createResponse(429, 'Too many requests');
        }

        return $handler->handle($request);
    }
}