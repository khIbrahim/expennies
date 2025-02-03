<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Contracts\UserProviderServiceInterface;
use App\DTO\AuthRegistrationData;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class UserProviderService implements UserProviderServiceInterface
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly HashService $hashService
    ){}

    public function getById(int $id): ?UserInterface
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function getByCredentials(array $credentials): ?UserInterface
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);
    }

    public function create(AuthRegistrationData $data): ?UserInterface
    {
        $user = (new User())
            ->setName($data->name)
            ->setEmail($data->email)
            ->setPassword($this->hashService->hashPassword($data->password))
            ->setTwoFactorEnabled($data->twoFactorEnabled);

        $this->entityManagerService->sync($user);
        return $user;
    }

    public function verify(User $user): void
    {
        $user->setVerifiedAt(new DateTime());

        $this->entityManagerService->sync($user);
    }

    public function update(User $user, AuthRegistrationData $data): void
    {
        $user->setName($data->name)
            ->setEmail($data->email)
            ->setTwoFactorEnabled($data->twoFactorEnabled)
            ->setPassword($data->password);

        $this->entityManagerService->sync($user);
    }

    public function updatePassword(User $user, string $newPassword): void
    {
        $user->setPassword($this->hashService->hashPassword($newPassword));

        $this->entityManagerService->sync($user);
    }
}