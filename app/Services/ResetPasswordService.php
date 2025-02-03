<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Entity\ResetPassword;
use App\Entity\User;
use Doctrine\ORM\NonUniqueResultException;

class ResetPasswordService
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly UserProviderServiceInterface $userProviderService,
    ){}

    public function generate(string $email): ResetPassword
    {
        $resetPassword = new ResetPassword();
        $resetPassword->setEmail($email);
        $resetPassword->setToken(bin2hex(random_bytes(32)));
        $resetPassword->setExpiration(new \DateTime('+3 minutes'));

        $this->entityManagerService->sync($resetPassword);

        return $resetPassword;
    }

    public function deactivateAllResetPasswords(string $email): void
    {
        $query = $this->entityManagerService->getRepository(ResetPassword::class)
            ->createQueryBuilder('rp')
            ->update()
            ->set('rp.active', 0)
            ->where('rp.email = :email')->setParameter('email', $email)
            ->andWhere('rp.active = 1')
            ->getQuery();

        $query->execute();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByToken(string $token): ?ResetPassword
    {
        return $this->entityManagerService->getRepository(ResetPassword::class)
            ->createQueryBuilder('rp')
            ->select('rp')
            ->where('rp.token = :token')->setParameter('token', $token)
            ->andWhere('rp.active = :active')->setParameter('active', 1)
            ->andWhere('rp.expiration > :now')->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function resetPassword(User $user, string $password): void
    {
        $this->entityManagerService->wrapInTransaction(function () use ($user, $password) {
            $this->deactivateAllResetPasswords($user->getEmail());

            $this->userProviderService->updatePassword($user, $password);
        });
    }

}