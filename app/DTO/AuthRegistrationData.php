<?php

namespace App\DTO;

class AuthRegistrationData
{

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly bool   $twoFactorEnabled
    ){}

}