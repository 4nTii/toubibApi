<?php

namespace App\Service\Mailer;

use App\Entity\Users;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class UserMailerService
{
    private MailerInterface $mailer;
    private Environment $twig;

    private string $fromContact;
    private string $fromInfo;
    private string $fromNoReply;
    private string $fromSupport;
    private string $fromAdmin;
    private string $fromNews;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,

        string $mailerFromContact,
        string $mailerFromInfo,
        string $mailerFromNoReply,
        string $mailerFromSupport,
        string $mailerFromAdmin,
        string $mailerFromNews,
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;

        $this->fromContact = $mailerFromContact;
        $this->fromInfo = $mailerFromInfo;
        $this->fromNoReply = $mailerFromNoReply;
        $this->fromSupport = $mailerFromSupport;
        $this->fromAdmin = $mailerFromAdmin;
        $this->fromNews = $mailerFromNews;
    }

    public function sendVerificationEmail(Users $user): bool
    {
        $email = (new Email())
            ->from($this->fromNoReply)
            ->to($user->getEmail())
            ->subject('Veuillez vérifier votre compte')
            ->html(
                $this->twig->render('emails/verify_account.html.twig', [
                    'user' => $user,
                ])
            );

        $this->mailer->send($email);

        return true;
    }

    public function sendPatientCreationEmail(Users $user, string $plainPassword): bool
    {
        $email = (new Email())
            ->from($this->fromNoReply)
            ->to($user->getEmail())
            ->subject('Votre compte Toubib a été créé')
            ->html(
                $this->twig->render('emails/patient_created.html.twig', [
                    'user'     => $user,
                    'password' => $plainPassword,
                ])
            );

        $this->mailer->send($email);

        return true;
    }

    public function sendPasswordResetEmail(Users $user, string $token): bool
    {
        $email = (new Email())
            ->from($this->fromNoReply)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html(
                $this->twig->render('emails/reset_password.html.twig', [
                    'user'  => $user,
                    'token' => $token,
                ])
            );

        $this->mailer->send($email);

        return true;
    }
}
