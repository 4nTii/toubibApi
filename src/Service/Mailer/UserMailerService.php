<?php

namespace App\Service\Mailer;

use App\Entity\Users;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class UserMailerService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private KernelInterface $kernel;

    private string $fromContact;
    private string $fromInfo;
    private string $fromNoReply;
    private string $fromSupport;
    private string $fromAdmin;
    private string $fromNews;

    private string $mailerApiUri;
    private string $mailerApiToken;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        KernelInterface $kernel,

        string $mailerFromContact,
        string $mailerFromInfo,
        string $mailerFromNoReply,
        string $mailerFromSupport,
        string $mailerFromAdmin,
        string $mailerFromNews,
        string $mailerApiUri,
        string $mailerApiToken,
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->kernel = $kernel;

        $this->fromContact = $mailerFromContact;
        $this->fromInfo = $mailerFromInfo;
        $this->fromNoReply = $mailerFromNoReply;
        $this->fromSupport = $mailerFromSupport;
        $this->fromAdmin = $mailerFromAdmin;
        $this->fromNews = $mailerFromNews;
        $this->mailerApiUri = $mailerApiUri;
        $this->mailerApiToken = $mailerApiToken;
    }

    public function sendVerificationEmail(Users $user): bool
    {
        $sendStatus = false;
        $env = $this->kernel->getEnvironment();
        if ($env === 'prod') {
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
            $sendStatus = true;
        } else {
            $client = HttpClient::create();
            // Email not going
            $payload = [
                'token' => $this->mailerApiToken,
                'from' => 'contact@store-banne-rentoilage.com',
                'to' => $user->getEmail(),
                'subject' => 'Bienvenue sur Toubib',
                'html' => $this->twig->render('emails/verify_account.html.twig', [
                    'user' => $user
                ])
            ];

            $response = $client->request('POST', $this->mailerApiUri, [

                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'ToubibAPI/0.1a',
                ],
                'body' => json_encode($payload)
            ]);

            $content = $response->getContent(false);

            $data = json_decode($content, true);
            return !empty($data['status']);
        }
        return $sendStatus;
    }

    public function sendPasswordResetEmail(Users $user, string $token): bool
    {
        $sendStatus = false;
        $env = $this->kernel->getEnvironment();

        if ($env === 'prod') {
            $email = (new Email())
                ->from($this->fromNoReply)
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html(
                    $this->twig->render('emails/reset_password.html.twig', [
                        'user' => $user,
                        'token' => $token,
                    ])
                );
            $this->mailer->send($email);
            $sendStatus = true;
        } else {
            $client = HttpClient::create();
            $payload = [
                'token' => $this->mailerApiToken,
                'from' => 'contact@store-banne-rentoilage.com',
                'to' => $user->getEmail(),
                'subject' => 'Réinitialisation de votre mot de passe',
                'html' => $this->twig->render('emails/reset_password.html.twig', [
                    'user' => $user,
                    'token' => $token,
                ])
            ];
            $response = $client->request('POST', $this->mailerApiUri, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'ToubibAPI/0.1a',
                ],
                'body' => json_encode($payload)
            ]);
            $content = $response->getContent(false);
            $data = json_decode($content, true);
            return !empty($data['status']);
        }

        return $sendStatus;
    }
}
