<?php

namespace App;

use App\Contracts\EntityManagerServiceInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use Slim\Interfaces\InvocationStrategyInterface;

class RouteEntityBindingStrategy implements InvocationStrategyInterface
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly ResponseFactoryInterface      $responseFactory
    ){}

    /**
     * @throws ReflectionException
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        $callableReflection = $this->createReflectionFunction($callable);
        $resolvedArguments  = [];

        foreach ($callableReflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type) {
                continue;
            }

            $paramName = $parameter->getName();
            $typeName  = $type->getName();

            if ($type->isBuiltin()) {
                if ($typeName === 'array' && $paramName === 'args') {
                    $resolvedArguments[] = $routeArguments;
                }
            } else {
                if ($typeName === ServerRequestInterface::class) {
                    $resolvedArguments[] = $request;
                } elseif ($typeName === ResponseInterface::class) {
                    $resolvedArguments[] = $response;
                } else {
                    //routeArguments → args dans la méthode de slim php de base*
                    //ces paramètres sont générés automatiquement quand tu ajoutes un argument dans le uri de la route,
                    //c'est-à-dire que /route/{test}
                    //test sera dans $routeArguments
                    //donc là, le but est de chercher test dans les paramètres de routeArgument, ensuite si on l'a bah on a automatiquement
                    //l'entité et sa classe avec $typeName
                    //paramName ce sont les paramètres qu'on ajoute dans la méthode (qu'il n'y a pas de base dans la méthode slim ta3 php)
                    $entityId = $routeArguments[$paramName] ?? null;

                    if (! $entityId || $parameter->allowsNull()) {
                        throw new \InvalidArgumentException(
                            'Unable to resolve argument "' . $paramName . '" in the callable'
                        );
                    }

                    $entity = $this->entityManagerService->find($typeName, $entityId);

                    if (! $entity) {
                        return $this->responseFactory->createResponse(404, 'Resource Not Found');
                    }

                    $resolvedArguments[] = $entity;
                }
            }
        }

        return $callable(...$resolvedArguments);
    }

    /**
     * @throws ReflectionException
     */
    public function createReflectionFunction(callable $callable): \ReflectionFunctionAbstract
    {
        if(is_array($callable)){
            $controller = $callable[0];
            $method = $callable[1];
            return new \ReflectionMethod($controller, $method);
        } else {
            return new \ReflectionFunction($callable);
        }
    }

}