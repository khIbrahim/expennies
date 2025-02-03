<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class ProfileUpdateRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $validator = new Validator($data);

        $validator->rule('required', ['name', 'twoFactor']);
        $validator->rule('lengthMin', 'name', 2);
        $validator->rule('lengthMax', 'name', 50);
        $validator->rule('in', 'twoFactor', [0, 1]);

        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }
}