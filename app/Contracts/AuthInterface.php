<?php

namespace App\Contracts;

use App\DTO\AuthRegistrationData;
use App\Enum\AuthAttemptStatus;

interface AuthInterface
{

    public function user(): ?UserInterface;

    public function attemptLogin(array $credentials): AuthAttemptStatus;

    public function checkCredentials(UserInterface $user, array $credentials): bool;

    public function register(AuthRegistrationData $data);

    public function logIn(UserInterface $user): void;

}