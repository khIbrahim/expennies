<?php

namespace App\Controllers;

use App\Contracts\SessionInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Entity\User;
use App\Mail\SignupEmail;
use DateInterval;
use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class VerifyEmailController
{

    public function __construct(
        private readonly Twig                         $twig,
        private readonly UserProviderServiceInterface $userProvider,
        private readonly SignupEmail                  $signupEmail,
        private readonly SessionInterface             $session
    ){}

    public function index(Request $request, Response $response): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        if($user->getVerifiedAt()){
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        $message = 'Email was sent to ' . $user->getEmail();

        $emailSentAt = $this->session->get('email_sent_at');
        if($emailSentAt !== null){
            $emailSentAt = clone $emailSentAt;
            if($emailSentAt->add(new DateInterval('PT30M')) < new DateTime()){
                $this->signupEmail->sendEmail($user);
            } else {
                $message = 'Email already sent';
            }
        } else {
            $this->signupEmail->sendEmail($user);
        }

        return $this->twig->render($response, 'auth/verify.twig', [
            'sent' => $message
        ]);
    }
    
    public function verify(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        if(! hash_equals((string) $user->getId(), $args['id']) || ! hash_equals(sha1($user->getEmail()), $args['hash'])){
            throw new \RuntimeException('La vérification a échoué');
        }

        if(! $user->getVerifiedAt()){
            $this->userProvider->verify($user);
        }

        return $response->withHeader('Location', '/')->withStatus(302);
    }

}