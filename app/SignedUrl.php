<?php

namespace App;

use Slim\Interfaces\RouteParserInterface;

class SignedUrl
{

    public function __construct(
        private readonly Config $config,
        private readonly RouteParserInterface $routeParser
    ){}

    public function fromRoute(string $routeName, array $routeParams, \DateTime $expirationDate): string
    {
        $baseUrl = trim($this->config->get('app_url'), '/');
        $queryParams = [
            'expiration' => $expirationDate->getTimestamp(),
        ];

        $url = $baseUrl . $this->routeParser->urlFor($routeName, $routeParams, $queryParams);
        $signature = hash_hmac('sha256', $url, $this->config->get('app_key'));

        return $baseUrl . $this->routeParser->urlFor(
            $routeName,
            $routeParams,
            $queryParams + ['signature' => $signature]
        );
    }

}