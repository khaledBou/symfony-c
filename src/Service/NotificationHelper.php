<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Notification\Notification;
use App\Entity\User\ImportableUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des notifications.
 *
 * Contient tout ce qui ne peut pas être fait dans
 * le contrôleur App\Notification\NotificationController.
 */
class NotificationHelper
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @param RouterInterface        $router
     */
    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator, RouterInterface $router)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->router = $router;
    }

    /**
     * Indique si un utilisateur a des notifications.
     *
     * @param ImportableUserInterface $user
     * @param bool|null               $read
     *
     * @return bool
     */
    public function hasNotifications(ImportableUserInterface $user, ?bool $read): bool
    {
        return $this
            ->em
            ->getRepository(Notification::class)
            ->countNotifications($user, $read) > 0
        ;
    }

    /**
     * Récupère les notifications d'un utilisateur,
     * en vue de les afficher.
     *
     * Une limite est appliquée pour limiter le nombre de requêtes Doctrine
     * et optimiser les performances d'affichage.
     *
     * @param ImportableUserInterface $user
     * @param int|null                $limit
     *
     * @return Notification[]
     */
    public function getNotifications(ImportableUserInterface $user, ?int $limit = null): array
    {
        return $this
            ->em
            ->getRepository(Notification::class)
            ->findBy(
                ['user' => $user],
                ['date' => 'desc'],
                $limit
            )
        ;
    }

    /**
     * Crée une notification pour un coach,
     * lorsqu'un de ses négociateurs réalise un événement important.
     *
     * @param \App\Entity\Event\EventInterface $event L'événement réalisé par le négociateur
     *
     * @return bool
     */
    public function addEventNotification(\App\Entity\Event\EventInterface $event): bool
    {
        $mandatary = $event->getMandatary();
        $coach = $mandatary->getCoach();

        // Sans coach, personne à notifier
        if (null === $coach) {
            return false;
        }

        /**
         * Date de l'événement en nombre de jours par rapport à maintenant :
         *
         * +n ou n : événement à venir dans n jours
         * -n : événement passé de n jours
         *
         * @var int
         */
        $days = (int) (new \DateTime())->diff($event->getDate())->format('%R%a');

        // Événement passé ?
        $isPastEvent = $event->getDate() <= new \DateTime();

        // Pas de notification si l'événement est passé depuis plus de 7 jours
        if ($isPastEvent && $days < -7) {
            return false;
        }

        // Par défaut, pas de message donc pas de notification
        $message = null;

        switch ($this->em->getMetadataFactory()->getMetadataFor(get_class($event))->getName()) {
            case 'App\Entity\Event\NthTradeEvent':
                $nth = $event->getNth();
                $message = 1 === $nth ?
                    $this->translator->trans("1er mandat") :
                    $this->translator->trans("%nth%ème mandat", ['%nth%' => $nth])
                ;
                break;
            case 'App\Entity\Event\NthCompromiseEvent':
                $nth = $event->getNth();
                $message = 1 === $nth ?
                    $this->translator->trans("1er compromis") :
                    $this->translator->trans("%nth%ème compromis", ['%nth%' => $nth])
                ;
                break;
            case 'App\Entity\Event\NthSaleEvent':
                $nth = $event->getNth();
                $message = 1 === $nth ?
                    $this->translator->trans("1ère vente") :
                    $this->translator->trans("%nth%ème vente", ['%nth%' => $nth])
                ;
                break;
            case 'App\Entity\Event\TradeShortfallEvent':
                $days = $event->getDaysSinceLastTrade();
                $message = 1 === $days ?
                    $this->translator->trans("1 jour sans mandat") :
                    $this->translator->trans("%days% jours sans mandat", ['%days%' => $days])
                ;
                break;
            case 'App\Entity\Event\CompromiseShortfallEvent':
                $days = $event->getDaysSinceLastCompromise();
                $message = 1 === $days ?
                    $this->translator->trans("1 jour sans compromis") :
                    $this->translator->trans("%days% jours sans compromis", ['%days%' => $days])
                ;
                break;
            case 'App\Entity\Event\SaleShortfallEvent':
                $days = $event->getDaysSinceLastSale();
                $message = 1 === $days ?
                    $this->translator->trans("1 jour sans vente") :
                    $this->translator->trans("%days% jours sans vente", ['%days%' => $days])
                ;
                break;
            case 'App\Entity\Event\TerminationEvent':
                $message = $this->translator->trans("Demande de résiliation reçue");
                break;
            case 'App\Entity\Event\FreshdeskFeedbackEvent':
                $rating = $event->getRating();
                if (in_array($rating, [
                    Event\FreshdeskFeedbackEvent::RATING_UNHAPPY,
                    Event\FreshdeskFeedbackEvent::RATING_VERY_UNHAPPY,
                    Event\FreshdeskFeedbackEvent::RATING_EXTREMELY_UNHAPPY,
                ])) {
                    $message = $this->translator->trans("%mandatary% a laissé une mauvaise évaluation sur Freshdesk", [
                        '%mandatary%' => $mandatary->getFirstName(),
                    ]);
                }
                break;
        }

        // Sans message, pas de notification
        if (null === $message) {
            return false;
        }

        $url = $this->router->generate('mandatary_show', [
            'slug' => $mandatary->getSlug(),
        ]);

        return $this->addNotification($coach, $message, $url, $mandatary);
    }

    /**
     * Crée une notification pour un coach,
     * à propos d'un négociateur.
     *
     * @param \App\Entity\User\Mandatary $mandatary
     * @param string                     $message
     *
     * @return bool
     */
    public function addMandataryNotification(\App\Entity\User\Mandatary $mandatary, string $message): bool
    {
        $coach = $mandatary->getCoach();

        // Sans coach, personne à notifier
        if (null === $coach) {
            return false;
        }

        $url = $this->router->generate('mandatary_show', [
            'slug' => $mandatary->getSlug(),
        ]);

        return $this->addNotification($coach, $message, $url, $mandatary);
    }

    /**
     * Ajoute une notification à un utilisateur,
     * après avoir vérifié qu'il n'existait pas de notification identique récente.
     *
     * @param ImportableUserInterface      $user
     * @param string                       $message
     * @param string                       $url
     * @param ImportableUserInterface|null $initiator
     *
     * @return bool
     */
    public function addNotification(ImportableUserInterface $user, string $message, string $url, ?ImportableUserInterface $initiator = null): bool
    {
        $added = false;

        /**
         * Notification identique récente.
         *
         * @var Notification|null
         */
        $recentNotification = $this->em->getRepository(Notification::class)->findRecent($user, $message, $initiator);

        if (null === $recentNotification) {
            $notification = (new Notification())
                ->setUser($user)
                ->setMessage($message)
                ->setUrl($url)
                ->setInitiator($initiator)
            ;

            $this->em->persist($notification);
            $this->em->flush();

            $added = true;
        }

        return $added;
    }
}
