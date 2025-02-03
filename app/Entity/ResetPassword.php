<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'reset_passwords')]
class ResetPassword
{

    #[Id, GeneratedValue, Column(options: ['unsigned' => true])]
    protected int $id;

    #[Column(type: 'string', length: 255)]
    private string $token;

    #[Column(type: 'string', length: 255)]
    private string $email;

    #[Column(name: 'active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $expiration;

    public function setExpiration(\DateTime $expiration): ResetPassword
    {
        $this->expiration = $expiration;
        return $this;
    }

    public function getExpiration(): \DateTime
    {
        return $this->expiration;
    }

    public function setActive(bool $active): ResetPassword
    {
        $this->active = $active;
        return $this;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function setToken(string $token): ResetPassword
    {
        $this->token = $token;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setEmail(string $email): ResetPassword
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): int
    {
        return $this->id;
    }

}