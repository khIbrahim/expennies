<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table('user_login_codes')]
class UserLoginCode
{
    #[Id, GeneratedValue, Column(options: ['unsigned' => true])]
    protected int $id;

    #[Column(length: 6)]
    private string $code;

    #[Column(name: 'is_active', options: ['default' => true])]
    private bool $active;

    #[Column]
    private \DateTime $expiration;

    #[ManyToOne(targetEntity: User::class, inversedBy: 'user_login_codes')]
    private User $user;

    public function __construct()
    {
        $this->active = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): UserLoginCode
    {
        $this->code = $code;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): UserLoginCode
    {
        $this->active = $active;

        return $this;
    }

    public function getExpiration(): \DateTime
    {
        return $this->expiration;
    }

    public function setExpiration(\DateTime $expiration): UserLoginCode
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserLoginCode
    {
        $this->user = $user;

        return $this;
    }
}