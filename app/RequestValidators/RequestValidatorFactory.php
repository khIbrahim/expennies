<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\RequestValidatorInterface;
use App\Exception\InvalidRequestValidator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RequestValidatorFactory implements RequestValidatorFactoryInterface
{

    public function __construct(
        private readonly ContainerInterface $container
    ){}

    /**
     * @throws InvalidRequestValidator
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function make(string $class): RequestValidatorInterface
    {
        $requestValidator = $this->container->get($class);

        if(! $requestValidator instanceof RequestValidatorInterface){
            throw new InvalidRequestValidator("Invalid request validator '$class'");
        }

        return $requestValidator;
    }
}