<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Entity\User;
use App\Exception\ValidationException;
use Valitron\Validator;

class UpdatePasswordRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);

        /** @var User $user */
        $user = $data['user'];

        $v->rule('required', ['newPassword', 'currentPassword'])->message("Required Field");

        $v->rule(function ($field, $value, $params, $fields) use ($user) {
            return password_verify($value, $user->getPassword());
        }, 'currentPassword')->message('Invalid Current Password');

        $v->rule('lengthMin', 'newPassword', 6)->message('Password must contain at least 6 characters');
        $v->rule('regex', 'newPassword', '/[A-Z]/')->message('Password must contain at least 1 uppercase letter');
        $v->rule('regex', 'newPassword', '/[0-9]/')->message('Password must contain at least 1 number');
        $v->rule('regex', 'newPassword', '/[\W_]/')->message('Password must contain at least 1 special character');

        if(! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        unset($data['user']);

        return $data;
    }
}