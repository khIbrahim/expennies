<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Exception\ValidationException;
use App\Services\ResetPasswordService;
use Valitron\Validator;

class ResetPasswordRequestValidator implements RequestValidatorInterface
{

    public function __construct(
        private readonly ResetPasswordService $resetPasswordService,
        private readonly UserProviderServiceInterface $userProviderService
    ){}

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['password', 'confirmPassword'])
            ->message('Required Field');
        $v->rule('equals', 'password', 'confirmPassword');
        $v->rule('lengthMin', 'password', 6)->message('Password must contain at least 6 characters');
        $v->rule('regex', 'password', '/[A-Z]/')->message('Password must contain at least 1 uppercase letter');
        $v->rule('regex', 'password', '/[0-9]/')->message('Password must contain at least 1 number');
        $v->rule('regex', 'password', '/[\W_]/')->message('Password must contain at least 1 special character');

        if(! $v->validate()){
            throw new ValidationException($v->errors());
        }

        $token = (string) $data['token'];
        $resetPassword = $this->resetPasswordService->findByToken($token);
        if(! $resetPassword){
            throw new ValidationException(['confirmPassword' => 'Invalid Token']);
        }

        $user = $this->userProviderService->getByCredentials(['email' => $resetPassword->getEmail()]);
        if(! $user){
            throw new ValidationException(['confirmPassword' => 'Invalid Token']);
        }

        $data['user'] = $user;

        return $data;
    }
}