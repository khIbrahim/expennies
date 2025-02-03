<?php

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DTO\AuthRegistrationData;
use App\Entity\User;
use App\RequestValidators\ProfileUpdateRequestValidator;
use App\RequestValidators\UpdatePasswordRequestValidator;
use App\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ProfileController
{

    public function __construct(
        private readonly Twig $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly ResponseFormatter $responseFormatter,
        private readonly UserProviderServiceInterface $userProvider
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(Request $request, Response $response): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $this->twig->render($response, 'profile/index.twig', [
            'profile' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'twoFactor' => $user->hasTwoFactorEnabled()
            ]
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(ProfileUpdateRequestValidator::class)->validate(
            $request->getParsedBody()
        );

        /** @var User $user */
        $user = $request->getAttribute('user');

        $authRegistrationData = new AuthRegistrationData(
            $data['name'], $user->getEmail(), $user->getPassword(), (bool) $data['twoFactor']
        );

        $this->userProvider->update($user, $authRegistrationData);

        return $this->responseFormatter->asJson($response, [
            'name'      => $authRegistrationData->name,
            'twoFactor' => (string) ((int) $authRegistrationData->twoFactorEnabled)
        ]);
    }

    public function updatePassword(Request $request, Response $response): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');
        $data = $this->requestValidatorFactory->make(UpdatePasswordRequestValidator::class)->validate(
            $request->getParsedBody() + ['user' => $user]
        );

        $this->userProvider->updatePassword($user, (string) $data['newPassword']);

        return $response;
    }

}