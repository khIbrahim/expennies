<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Entity\User;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Valitron\Validator;

class RegistrationRequestValidator implements RequestValidatorInterface
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['name', 'email', 'password', 'confirmPassword']);
        $v->rule('email', 'email');
        $v->rule('equals', 'confirmPassword', 'password')
            ->message('Password and confirm password must be equals');
        $v->rule(function ($field, $value, $params, $fields){
            return $this->entityManager->getRepository(User::class)->count(['email' => $value]) <= 0;
        }, 'email')->message('User with the given email address already exists');
        $v->rule('in', 'twoFactorAuth', [0, 1]);
        if(! $v->validate()) {
            var_dump($v->errors());
            throw new ValidationException($v->errors());
        }

        $data['twoFactorAuth'] = (bool) $data['twoFactorAuth'];

        return $data;
    }
}