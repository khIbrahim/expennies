<?php

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Entity\User;
use App\Mail\ResetPasswordEmail;
use App\RequestValidators\ForgotPasswordRequestValidator;
use App\RequestValidators\ResetPasswordRequestValidator;
use App\ResponseFormatter;
use App\Services\ResetPasswordService;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ResetPasswordController
{

    public function __construct(
        private readonly Twig $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly ResponseFormatter $responseFormatter,
        private readonly ResetPasswordEmail $resetPasswordEmail,
        private readonly ResetPasswordService $resetPasswordService,
        private readonly UserProviderServiceInterface $userProviderService
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(Response $response): Response
    {
        return $this->twig->render($response, 'auth/forgot_password.twig');
    }

    public function send(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(ForgotPasswordRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        $email = (string) $data['email'];
        $user  = $this->userProviderService->getByCredentials(['email' => $email]);

        if($user){
            $this->resetPasswordService->deactivateAllResetPasswords($email);

            $this->resetPasswordEmail->send($this->resetPasswordService->generate($email));
        }

        return $this->responseFormatter->asJson($response, [
            'sent' => true
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws NonUniqueResultException
     * @throws LoaderError
     */
    public function sendResetPasswordForm(Response $response, array $args): Response
    {
        $token = (string) $args['token'];
        $resetPassword = $this->resetPasswordService->findByToken($token);

        if(! $resetPassword){
            return $response->withHeader('location', '/')->withStatus(302);
        }

        return $this->twig->render($response, 'auth/reset_password.twig', [
            'token' => $token
        ]);
    }

    public function verify(Request $request, Response $response, array $args): Response
    {
        $data = $this->requestValidatorFactory->make(ResetPasswordRequestValidator::class)->validate(
            $request->getParsedBody() + ['token' => $args['token']]
        );

        /** @var User $user */
        $user = $data['user'];
        $this->resetPasswordService->resetPassword($user, $data['confirmPassword']);

        return $response;
    }

}