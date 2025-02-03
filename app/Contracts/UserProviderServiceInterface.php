<?php

namespace App\Contracts;

use App\DTO\AuthRegistrationData;
use App\Entity\User;

interface UserProviderServiceInterface
{

    public function getById(int $id) : ?UserInterface;

    public function getByCredentials(array $credentials) : ?UserInterface;

    public function create(AuthRegistrationData $data) : ?UserInterface;

    public function verify(User $user): void;

    public function update(User $user, AuthRegistrationData $data): void;

    public function updatePassword(User $user, string $newPassword);

}