<?php

namespace App\Service\Calendar;

use App\Entity\Event\AppointmentEvent;
use App\Entity\User;
use App\Service\GoogleOAuthHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper de gestion de l'agenda Google Calendar.
 *
 * @see https://developers.google.com/calendar/quickstart/php
 */
class GoogleCalendarHelper extends AbstractCalendarHelper
{
    /**
     * @var GoogleOAuthHelper
     */
    private $googleOAuthHelper;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * GoogleCalendarHelper constructor.
     *
     * @param EntityManagerInterface $em
     * @param SessionInterface       $session
     * @param TranslatorInterface    $translator
     * @param GoogleOAuthHelper      $googleOAuthHelper
     * @param ParameterBagInterface  $parameterBag
     *
     * @throws \Google_Exception
     */
    public function __construct(EntityManagerInterface $em, SessionInterface $session, TranslatorInterface $translator, GoogleOAuthHelper $googleOAuthHelper, ParameterBagInterface $parameterBag)
    {
        parent::__construct($em, $session, $translator);

        $googleOAuthHelper
            ->setClientScopes([
                \Google_Service_Calendar::CALENDAR,
            ])
            ->setClientAuthConfig('config/google/credentials/code_secret_client_google_oauth_calendar.json');
        ;

        $this->googleOAuthHelper = $googleOAuthHelper;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * @see https://developers.google.com/calendar/v3/reference/events/insert#examples
     *
     * @inheritdoc
     */
    public function pushToCalendar(AppointmentEvent $event, User\ImportableUserInterface $user): bool
    {
        if ($this->isDev) {
            $this
                ->getSession()
                ->getFlashBag()
                ->add('notice', $this->getTranslator()->trans("Mode dev : pas de création de l'événement dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;

            return false;
        }

        // Dates de début et de fin
        $startDate = $event->getDate();
        $endDate = clone $startDate;
        $endDate->add($event->getDuration());

        // L'invité
        if ($user instanceof User\Coach) {
            $attendee = $event->getMandatary();
        } elseif ($user instanceof User\Mandatary) {
            $attendee = $event->getCoach();
        } else {
            $attendee = null;
        }

        // Titre
        if ($attendee) {
            $summary = sprintf("%s", $event->getSubject());
        } else {
            $summary = $event->getSubject();
        }

        /**
         * Les coachs PP ont une adresse e-mail @proprietes-privees.com
         * alors que leur compte Google (avec lequel ils sont connectés) est en @proprietes-privees.fr.
         *
         * Cette incohérence ne permet pas la prise en compte du paramètre 'responseStatus' dans l'appel API.
         *
         * Pour ceux là uniquement, on utilise un hack de façon à ce que la variable $email
         * corresponde au compte Google avec lequel ils sont connectés.
         *
         * @see https://app.asana.com/0/1127556459669466/1166643915936889
         */
        $email = $user->getEmail();
        $domain = substr($email, strpos($email, '@') + 1);

        if ($user instanceof \App\Entity\User\Coach && 'proprietes-privees.com' === $domain) {
            $email = str_replace($domain, 'proprietes-privees.fr', $email);
        }

        $calendarEventParams = [
            'summary' => $summary,
            'start' => [
                'dateTime' => $startDate->format(\DateTimeInterface::RFC3339),
                'timeZone' => 'Europe/Paris',
            ],
            'end' => [
                'dateTime' => $endDate->format(\DateTimeInterface::RFC3339),
                'timeZone' => 'Europe/Paris',
            ],
            'attendees' => [
                [
                    'email' => $email,
                    'displayName' => (string) $user,
                    'responseStatus' => 'accepted',
                ],
                [
                    'email' => $attendee->getEmail(),
                    'displayName' => (string) $attendee,
                    'responseStatus' => 'accepted',
                ],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    [
                        'method' => 'email',
                        'minutes' => 1440, // 1 jour
                    ],
                    [
                        'method' => 'popup',
                        'minutes' => 10,
                    ],
                ],
            ],
            'sendUpdates' => 'none', // pas de notification aux participants
        ];

        // Description éventuelle
        $description = $event->getDescription();
        if (null !== $description) {
            $calendarEventParams['description'] = $description;
        }

        $created = false;

        try {
            $client = $this->googleOAuthHelper->getClient();
            $calendar = new \Google_Service_Calendar($client);
            $calendarEvent = new \Google_Service_Calendar_Event($calendarEventParams);

            /**
             * Appel de l'API.
             *
             * @var \Google_Service_Calendar_Event
             */
            $calendarEvent = $calendar->events->insert('primary', $calendarEvent);

            /**
             * Identifiant de l'événement Google Calendar.
             *
             * @var string
             */
            $googleCalendarId = $calendarEvent->getId();

            $this
                ->getSession()
                ->getFlashBag()
                ->add('success', $this->getTranslator()->trans("Événement créé dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;

            $this
                ->getSession()
                ->getFlashBag()
                ->add('success', $this->getTranslator()->trans("Événement créé dans l'agenda de %user%.", ['%user%' => (string) $attendee]))
            ;

            // Enregistre l'identifiant de l'événement Google Calendar
            $event->setGoogleCalendarId($googleCalendarId);
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();

            $created = true;
        } catch (\Google_Service_Exception $e) {
            $this
                ->getSession()
                ->getFlashBag()
                ->add('error', $this->getTranslator()->trans("Impossible de créer l'événement dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;

            $this
                ->getSession()
                ->getFlashBag()
                ->add('error', $this->getTranslator()->trans("Impossible de créer l'événement dans l'agenda de %user%.", ['%user%' => (string) $attendee]))
            ;
        }

        return $created;
    }

    /**
     * @see https://developers.google.com/calendar/v3/reference/events/delete#examples
     *
     * @inheritdoc
     */
    public function removeFromCalendar(AppointmentEvent $event, User\ImportableUserInterface $user): bool
    {
        if ($this->isDev) {
            $this
                ->getSession()
                ->getFlashBag()
                ->add('notice', $this->getTranslator()->trans("Mode dev : pas de suppression de l'événement dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;

            return false;
        }

        $removed = false;

        // @var string|null
        $googleCalendarId = $event->getGoogleCalendarId();

        if (null !== $googleCalendarId) {
            try {
                $client = $this->googleOAuthHelper->getClient();
                $calendar = new \Google_Service_Calendar($client);

                /**
                 * @var \GuzzleHttp\Psr7\Response
                 */
                $calendar->events->delete('primary', $event->getGoogleCalendarId(), [
                    'sendUpdates' => 'none', // pas de notification aux participants
                ]);

                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('success', $this->getTranslator()->trans("Événement supprimé de l'agenda de %user%.", ['%user%' => (string) $user]))
                ;

                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('success', $this->getTranslator()->trans("Événement supprimé de l'agenda de %user%.", ['%user%' => (string) $event->getMandatary()]))
                ;
            } catch (\Google_Service_Exception $e) {
                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('notice', $this->getTranslator()->trans("Événement déjà supprimé de l'agenda de %user%.", ['%user%' => (string) $user]))
                ;

                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('notice', $this->getTranslator()->trans("Événement déjà supprimé de l'agenda de %user%.", ['%user%' => (string) $event->getMandatary()]))
                ;
            }

            // Supprime l'identifiant de l'événement Google Calendar
            $event->setGoogleCalendarId(null);
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();

            $removed = true;
        }

        return $removed;
    }
}
