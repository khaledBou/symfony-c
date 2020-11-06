<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment as Twig;

/**
 * Gestion des e-mails.
 */
class EmailHelper
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var Twig
     */
    private $twig;

    /**
     * @param MailerInterface $mailer
     * @param Twig            $twig
     */
    public function __construct(MailerInterface $mailer, Twig $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Envoie un e-mail.
     *
     * @param string $subject
     * @param string $content
     * @param string $fromEmail
     * @param string $fromLabel
     * @param string $toEmail
     * @param string $toLabel
     * @param string $network
     * @param string $template
     * @param array  $additionalTemplateVariables
     *
     * @return bool Indique le succès de l'envoi
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $subject, string $content, string $fromEmail, string $fromLabel, string $toEmail, string $toLabel, string $network, string $template = 'email/default.%s.html.twig', array $additionalTemplateVariables = []): bool
    {
        $html = $this->twig->render(sprintf($template, $network), array_merge([
            'title' => $subject,
            'content' => $content,
            'sender' => $fromLabel,
        ], $additionalTemplateVariables));

        $message = (new TemplatedEmail())
            ->subject($subject)
            ->from(new Address($fromEmail, $fromLabel))
            ->to(new Address($toEmail, $toLabel))
            ->html($html)
        ;

        // PP veut recevoir sur cette adresse une copie de tous les mails envoyés
        if ('pp' === $network) {
            $message->bcc('testcrm@proprietes-privees.com');
        }

        return $this->mailer->send($message) > 0;
    }
}
