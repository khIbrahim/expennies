<?php

namespace App\Mail;

use App\Config;
use App\Contracts\SessionInterface;
use App\Entity\UserLoginCode;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class TwoFactorAuthEmail
{

    public function __construct(
        private readonly Config $config,
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $renderer,
        private readonly SessionInterface $session
    ){}

    /**
     * @throws TransportExceptionInterface
     */
    public function send(UserLoginCode $userLoginCode): void
    {
        $email = $userLoginCode->getUser()->getEmail();
        $expirationDate = new \DateTime('+30 minutes');

        $message = new TemplatedEmail();
        $message->from($this->config->get('mailer.from'));
        $message->to($email);
        $message->subject("Expennies Verification Code");
        $message->htmlTemplate('emails/two_factor.html.twig');
        $message->context([
            'code'           => $userLoginCode->getCode(),
            'expirationDate' => $expirationDate
        ]);

        $this->session->put('email_sent_at', new \DateTime());

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}