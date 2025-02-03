<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\Entity\User;
use App\Entity\UserLoginCode;

class UserLoginCodeService
{

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager
    ){}

    public function generate(User $user): UserLoginCode
    {
        $userLoginCode = new UserLoginCode();

        $userLoginCode->setCode((string) random_int(100000, 999999));
        $userLoginCode->setUser($user);
        $userLoginCode->setExpiration(new \DateTime('+10 minutes'));

        $this->entityManager->sync($userLoginCode);

        return $userLoginCode;
    }

    public function verify(UserInterface $user, int $code): bool
    {
        $userLoginCode = $this->entityManager->getRepository(UserLoginCode::class)->findOneBy([
            'user' => $user, 'code' => $code, 'active' => true
        ]);

        if(! $userLoginCode){
            return false;
        }

        if($userLoginCode->getExpiration() < new \DateTime('')){
            return false;
        }

        return true;
    }

    public function deactivateAllCodes(UserInterface $user): void
    {
        $this->entityManager->getRepository(UserLoginCode::class)
            ->createQueryBuilder('c')
            ->update()
            ->set('c.active', '0')
            ->where('c.user = :user')->setParameter('user', $user)
            ->andWhere('c.active = 1')
            ->getQuery()
        ->execute();
    }

}