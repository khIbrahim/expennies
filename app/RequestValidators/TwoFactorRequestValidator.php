<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class TwoFactorRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $validator = new Validator($data);

        $validator->rule('required', ['email', 'code']);
        $validator->rule('email', $data['email']);
        $validator->rule(['integer', 'length'], [(int) $data['id'], 6]);

        if (! $validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }
}