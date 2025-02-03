<?php
namespace App;

use App\Contracts\AuthInterface;
use App\Contracts\SessionInterface;
use App\Contracts\UserInterface;
use App\Enum\AuthAttemptStatus;
use App\Mail\SignupEmail;
use App\Mail\TwoFactorAuthEmail;
use App\Services\UserLoginCodeService;
use App\Services\UserProviderService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Auth implements AuthInterface {

    public function __construct(
        private readonly UserProviderService $userProvider,
        private readonly SessionInterface $session,
        private readonly SignupEmail $signupEmail,
        private readonly TwoFactorAuthEmail $twoFactorAuthEmail,
        private readonly UserLoginCodeService $userLoginCodeService
    ){}

    private ?UserInterface $user = null;

    public function user(): ?UserInterface
    {
        if ($this->user !== null){
            return $this->user;
        }

        $userId = $this->session->get('user') ?? null;
        if(! $userId){
            return null;
        }

        $user = $this->userProvider->getById($userId);
        $this->user = $user;
        return $this->user;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function attemptLogin(array $credentials): AuthAttemptStatus
    {
        $user = $this->userProvider->getByCredentials($credentials);
        if (! $user || ! $this->checkCredentials($user, $credentials)) {
            return AuthAttemptStatus::FAILED;
        }

        if($user->hasTwoFactorEnabled()){
            $this->startLoginWith2FA($user);

            return AuthAttemptStatus::TWO_FACTOR_AUTH;
        }

        $this->logIn($user);

        return AuthAttemptStatus::SUCCESS;
    }

    public function checkCredentials(UserInterface $user, array $credentials): bool
    {
        return password_verify($credentials['password'], $user->getPassword());
    }

    public function logOut() : void
    {
        $this->session->forget('user');
        $this->session->regenerate();

        $this->user = null;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function register(DTO\AuthRegistrationData $data): ?UserInterface
    {
        $user = $this->userProvider->create($data);

        $this->logIn($user);

        $this->signupEmail->sendEmail($user);

        return $user;
    }

    public function logIn(UserInterface $user) : void
    {
        $this->session->regenerate();
        $this->session->put('user', $user->getId());

        $this->user = $user;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function startLoginWith2FA(UserInterface $user): void
    {
        $this->session->regenerate();
        $this->session->put('f2a', $user->getId());

        $this->twoFactorAuthEmail->send($this->userLoginCodeService->generate($user));
    }

    public function attemptTwoFactorLogin(array $data): bool
    {
        $code  = (int)    $data['code'];
        $email = (string) $data['email'];

        $userId = $this->session->get('f2a');
        if(! $userId){
            return false;
        }

        $userId = (int) $userId;
        $user = $this->userProvider->getById($userId);

        if (! $user || $user->getEmail() !== $email) {
            return false;
        }

        if(! $this->userLoginCodeService->verify($user, $code)){
            return false;
        }

        $this->session->forget('f2a');
        $this->userLoginCodeService->deactivateAllCodes($user);

        $this->logIn($user);
        return true;
    }

}