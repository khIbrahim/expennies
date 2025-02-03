<?php

namespace App\Mail;

use App\Config;
use App\Contracts\SessionInterface;
use App\Entity\User;
use App\SignedUrl;
use Slim\Interfaces\RouteParserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class SignupEmail
{

    public function __construct(
        private readonly Config $config,
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $renderer,
        private readonly SignedUrl $signedUrl,
        private readonly SessionInterface $session
    ){}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(User $user): void
    {
        $email = $user->getEmail();
        $url = $this->signedUrl->fromRoute('verify', [
            'id' => $user->getId(),
            'hash' => sha1($email),
        ], new \DateTime('+30 minutes'));

        $message = new TemplatedEmail();
        $message->from($this->config->get('mailer.from'));
        $message->to($email);
        $message->subject("Welcome to expennies app");
        $message->htmlTemplate('emails/signup.twig');
        $message->context([
            'activationLink' => $url,
            'expirationDate' => new \DateTime('+30 minutes')
        ]);

        $this->session->put('email_sent_at', new \DateTime());

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}