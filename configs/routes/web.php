<?php

declare(strict_types = 1);

use App\Controllers\AuthController;
use App\Controllers\CategoriesController;
use App\Controllers\HomeController;
use App\Controllers\ImportTransactionsController;
use App\Controllers\ProfileController;
use App\Controllers\ReceiptController;
use App\Controllers\ResetPasswordController;
use App\Controllers\TransactionsController;
use App\Controllers\VerifyEmailController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\RateLimitMiddleware;
use App\Middlewares\VerifyEmailMiddleware;
use App\Middlewares\VerifySignatureMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/', [HomeController::class, 'index'])->setName('overview');
    })->add(VerifyEmailMiddleware::class)->add(AuthMiddleware::class);

    $app->group('/profile', function (RouteCollectorProxy $profile) {
        $profile->get('', [ProfileController::class, 'index']);
        $profile->post('/update', [ProfileController::class, 'update']);
        $profile->post('/updatePassword', [ProfileController::class, 'updatePassword']);
    })->add(VerifyEmailMiddleware::class)->add(AuthMiddleware::class);

    $app->group('', function(RouteCollectorProxy $guest) {
        $guest->get('/login', [AuthController::class, 'loginView']);
        $guest->get('/register', [AuthController::class, 'registerView']);

        $guest->post('/login', [AuthController::class, 'logIn'])
            ->setName('logIn')
            ->add(RateLimitMiddleware::class);
        $guest->post('/login/two-factor', [AuthController::class, 'twoFactorLogIn'])
            ->setName('twoFactorLogIn');
        $guest->post('/register', [AuthController::class, 'register'])
            ->setName('register')
            ->add(RateLimitMiddleware::class);

        $guest->get('/forgot-password', [ResetPasswordController::class, 'index'])
            ->setName('handleForgotPassword');
            //->add(RateLimitMiddleware::class);
        $guest->post('/forgot-password', [ResetPasswordController::class, 'send'])
            ->setName('sendForgotPassword')
            ->add(RateLimitMiddleware::class);
        $guest->get('/reset-password/{token}', [ResetPasswordController::class, 'sendResetPasswordForm'])
            ->setName('resetPassword')
            ->add(VerifySignatureMiddleware::class);
        $guest->post('/reset-password/{token}', [ResetPasswordController::class, 'verify'])
            ->add(RateLimitMiddleware::class);
    })->add(GuestMiddleware::class);

    $app->group('', function(RouteCollectorProxy $group) {
        $group->post('/logout', [AuthController::class, 'logOut']);
        $group->get('/verify', [VerifyEmailController::class, 'index']);
        $group->get('/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
            ->setName('verify')
            ->add(VerifySignatureMiddleware::class);
    })->add(AuthMiddleware::class);

    $app->group('/categories', function(RouteCollectorProxy $categories){
        $categories->get('', [CategoriesController::class, 'index'])->setName('categories');
        $categories->post('', [CategoriesController::class, 'store']);
        $categories->get('/load', [CategoriesController::class, 'load']);
        $categories->delete('/{id:[0-9]+}', [CategoriesController::class, 'delete']);
        $categories->get('/{id:[0-9]+}', [CategoriesController::class, 'get']);
        $categories->post('/{id:[0-9]+}', [CategoriesController::class, 'update']);
    })->add(VerifyEmailMiddleware::class)->add(AuthMiddleware::class);

    $app->group('/transactions', function(RouteCollectorProxy $transactions){
        $transactions->get('', [TransactionsController::class, 'index'])->setName('transactions');
        $transactions->get('/load', [TransactionsController::class, 'load']);
        $transactions->delete("/{transaction}", [TransactionsController::class, 'delete']);
        $transactions->get("/{transaction}", [TransactionsController::class, 'get']);
        $transactions->post("", [TransactionsController::class, 'store']);
        $transactions->post('/import', [ImportTransactionsController::class, 'import']);
        $transactions->post("/{transaction}", [TransactionsController::class, 'update']);
        $transactions->post("/{id:[0-9]+}/receipts", [ReceiptController::class, 'store']);
        $transactions->delete("/{transaction}/receipts/{receipt}", [ReceiptController::class, 'delete']);
        $transactions->get("/{transaction}/receipts/{receipt}", [ReceiptController::class, 'download']);
        $transactions->post("/{transaction}/review", [TransactionsController::class, 'toggleReviewed']);
    })->add(VerifyEmailMiddleware::class)->add(AuthMiddleware::class);

    $app->get('/stats/monthlySummaryChart', [HomeController::class, 'statsMonthlySummaryChart']);
};
