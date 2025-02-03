<?php

namespace App\Mail;

use App\Config;
use App\Contracts\SessionInterface;
use App\Entity\ResetPassword;
use App\SignedUrl;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\BodyRendererInterface;

class ResetPasswordEmail
{

    public function __construct(
        private readonly Config $config,
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $renderer,
        private readonly SignedUrl $signedUrl,
        private readonly SessionInterface $session
    ){}

    public function send(ResetPassword $resetPassword): void
    {
        $url = $this->signedUrl->fromRoute('resetPassword', [
            'token' => $resetPassword->getToken(),
        ], $resetPassword->getExpiration());

        $message = new TemplatedEmail();
        $message->to($resetPassword->getEmail());
        $message->from($this->config->get('mailer.from'));
        $message->to($resetPassword->getEmail());
        $message->subject("Update your expennies password");
        $message->htmlTemplate('emails/resetPassword.html.twig');
        $message->context([
            'activationLink' => $url,
            'expirationDate' => new \DateTime('+3 minutes')
        ]);

        $this->session->put('email_sent_at', new \DateTime());

        $this->renderer->render($message);

        $this->mailer->send($message);
    }

}