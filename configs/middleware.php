<?php

declare(strict_types = 1);

use App\Config;
use App\Enum\AppEnvironment;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfFieldsMiddleware;
use App\Middlewares\OldFormDataMiddleware;
use App\Middlewares\SessionStartMiddleware;
use App\Middlewares\ValidationErrorsMiddleware;
use App\Middlewares\ValidationExceptionMiddleware;
use App\Middlewares\VerifyEmailMiddleware;
use Clockwork\Support\Slim\ClockworkMiddleware;
use Slim\App;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Clockwork\Clockwork;

return function (App $app) {
    $container = $app->getContainer();
    $config    = $container->get(Config::class);

    $app->add(MethodOverrideMiddleware::class);
    $app->add(CsrfFieldsMiddleware::class);
    $app->add('csrf');
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(ValidationErrorsMiddleware::class);
    $app->add(OldFormDataMiddleware::class);
    $app->add(SessionStartMiddleware::class);
    $app->add(new ClockworkMiddleware($app, $container->get(Clockwork::class)));
    $app->addBodyParsingMiddleware();
    $app->addErrorMiddleware(
        (bool) $config->get('display_error_details'),
        (bool) $config->get('log_errors'),
        (bool) $config->get('log_error_details')
    );
};
