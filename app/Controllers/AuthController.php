<?php
declare(strict_types = 1);
namespace App\Controllers;

use App\Auth;
use App\Contracts\RequestValidatorFactoryInterface;
use App\DTO\AuthRegistrationData;
use App\Enum\AuthAttemptStatus;
use App\Exception\ValidationException;
use App\RequestValidators\LoginRequestValidator;
use App\RequestValidators\RegistrationRequestValidator;
use App\RequestValidators\TwoFactorRequestValidator;
use App\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{

    public function __construct(
        private readonly Twig $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly Auth $auth,
        private readonly ResponseFormatter $responseFormatter
    ){}

    public function loginView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/login.twig');
    }

    public function registerView(Response $response): Response
    {
        return $this->twig->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(RegistrationRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        $this->auth->register(
            new AuthRegistrationData($data['name'], $data['email'], $data['password'], $data['twoFactorAuth'])
        );

        return $response->withHeader('Location', '/register')->withStatus(302);
    }

    public function logOut(Response $response) : Response{
        $this->auth->logOut();

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logIn(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(LoginRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        $status = $this->auth->attemptLogin($data);

        if($status === AuthAttemptStatus::FAILED){
            throw new ValidationException(['password' => ['You have entered an invalid email or password']]);
        }

        if($status === AuthAttemptStatus::TWO_FACTOR_AUTH){
            return $this->responseFormatter->asJson($response, ['two_factor' => true]);
        }

        return $this->responseFormatter->asJson($response, []);
    }

    public function twoFactorLogIn(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(TwoFactorRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        if(! $this->auth->attemptTwoFactorLogin($data)){
            throw new ValidationException(['code' => 'Wrong code']);
        }

        return $this->responseFormatter->asJson($response, [
            'session' => $this->auth->user()
        ]);
    }

}