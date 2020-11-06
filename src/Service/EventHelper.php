<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use App\Service\Calendar\CalendarHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Gestion des événements.
 */
class EventHelper
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var CalendarHelperInterface[]
     */
    private $calendarHelpers;

    /**
     * @var SmsHelper
     */
    private $smsHelper;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param ParameterBagInterface      $parameterBag
     * @param CalendarHelperInterface    $googleCalendarHelper
     * @param CalendarHelperInterface    $zimbraCalendarHelper
     * @param SmsHelper                  $smsHelper
     * @param SessionInterface           $session
     * @param TranslatorInterface        $translator
     * @param EntityManagerInterface     $em
     * @param ProprietesPriveesApiHelper $proprietesPriveesApiHelper
     */
    public function __construct(ParameterBagInterface $parameterBag, CalendarHelperInterface $googleCalendarHelper, CalendarHelperInterface $zimbraCalendarHelper, SmsHelper $smsHelper, SessionInterface $session, TranslatorInterface $translator, EntityManagerInterface $em, ProprietesPriveesApiHelper $proprietesPriveesApiHelper)
    {
        $this->config = $parameterBag->get('app');
        $this->calendarHelpers[get_class($googleCalendarHelper)] = $googleCalendarHelper;
        $this->calendarHelpers[get_class($zimbraCalendarHelper)] = $zimbraCalendarHelper;
        $this->smsHelper = $smsHelper;
        $this->session = $session;
        $this->translator = $translator;
        $this->em = $em;
        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * Hook à la création des événements.
     *
     * @param Event\EventInterface $event
     *
     * @return void
     *
     * @throws \Exception Lorsque l'événement est un rendez-vous et que le(s) helper(s) de gestion des agendas n'ont pas été injectés
     */
    public function createHook(Event\EventInterface $event): void
    {
        $mandatary = $event->getMandatary();
        $coach = $event->getCoach();

        switch ($this->em->getMetadataFactory()->getMetadataFor(get_class($event))->getName()) {
            case 'App\Entity\Event\AppointmentEvent':
                // Création de l'événement dans l'agenda du coach
                if (null !== $coach) {
                    $coachCalendarHelper = $this->getCoachCalendarHelper($coach);
                    if (null !== $coachCalendarHelper) {
                        if (!isset($this->calendarHelpers[$coachCalendarHelper])) {
                            throw new \Exception(sprintf('Please inject %s as an interface in %s (also see service.yaml).', $coachCalendarHelper, self::class));
                        }
                        $this->calendarHelpers[$coachCalendarHelper]->pushToCalendar($event, $coach);
                    }
                }

                // Création de l'événement dans l'agenda du négociateur
                $mandataryCalendarHelper = $this->getMandataryCalendarHelper($mandatary);
                if (null !== $mandataryCalendarHelper) {
                    if (!isset($this->calendarHelpers[$mandataryCalendarHelper])) {
                        throw new \Exception(sprintf('Please inject %s as an interface in %s (also see service.yaml).', $mandataryCalendarHelper, self::class));
                    }

                    if ($mandataryCalendarHelper !== $coachCalendarHelper) { // Pour éviter de créer l'event deux fois dans chaque calendrier
                        $this->calendarHelpers[$mandataryCalendarHelper]->pushToCalendar($event, $mandatary);
                    }
                }

                // Envoi d'un SMS au négociateur pour lui annoncer le RDV, sauf en mode dev
                if (!$this->isDev) {
                    $sent = $this->smsHelper->sendEventSms($event);
                    if ($sent) {
                        $this
                            ->session
                            ->getFlashBag()
                            ->add('success', $this->translator->trans("SMS de rendez-vous envoyé à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                        ;
                    } else {
                        $this
                            ->session
                            ->getFlashBag()
                            ->add('error', $this->translator->trans("SMS de rendez-vous non envoyé à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                        ;
                    }
                } else {
                    $this
                        ->session
                        ->getFlashBag()
                        ->add('notice', $this->translator->trans("Mode dev : pas d'envoi de SMS de rendez-vous à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                    ;
                }
                break;
            case 'App\Entity\Event\CallEvent':
                if (!$this->isDev && 'pp' === $mandatary->getNetwork()) {
                    $output = $this->proprietesPriveesApiHelper->call('manage-mandatary', [
                        'mandatary_email' => $mandatary->getEmail(),
                        'employee_email' => null !== $coach ? $coach->getEmail() : $mandatary->getCoach()->getEmail(),
                        'date' => $event->getDate()->format('Y-m-d H:i:s'),
                        'message' => $event->isIncoming() ?
                            $this->translator->trans("Appel entrant :\n\n%report%.", ['%report%' => $event->getReport()]) :
                            $this->translator->trans("Appel sortant :\n\n%report%.", ['%report%' => $event->getReport()]),
                    ], 'POST');

                    if (!isset($output->success) || !$output->success) {
                        $this
                            ->session
                            ->getFlashBag()
                            ->add('error', $this->translator->trans("L'appel téléphonique n'a pas été synchronisé vers le CRM."))
                        ;
                    }
                }
                break;
            case 'App\Entity\Event\CommentEvent':
                if (!$this->isDev && 'pp' === $mandatary->getNetwork()) {
                    $output = $this->proprietesPriveesApiHelper->call('manage-mandatary', [
                        'mandatary_email' => $mandatary->getEmail(),
                        'employee_email' => $coach->getEmail(),
                        'date' => $event->getDate()->format('Y-m-d H:i:s'),
                        'message' => $event->getComment(),
                    ], 'POST');

                    if (!isset($output->success) || !$output->success) {
                        $this
                            ->session
                            ->getFlashBag()
                            ->add('error', $this->translator->trans("Le commentaire n'a pas été synchronisé vers le CRM."))
                        ;
                    }
                }
                break;
        }
    }

    /**
     * Hook à la suppression des événements.
     *
     * @param Event\EventInterface $event
     *
     * @throws \Exception
     */
    public function deleteHook(Event\EventInterface $event): void
    {
        $mandatary = $event->getMandatary();
        $coach = $event->getCoach();

        switch (get_class($event)) {
            case 'App\Entity\Event\AppointmentEvent':
                // Création de l'événement dans l'agenda du coach
                if (null !== $coach) {
                    $coachCalendarHelper = $this->getCoachCalendarHelper($coach);
                    if (null !== $coachCalendarHelper) {
                        if (!isset($this->calendarHelpers[$coachCalendarHelper])) {
                            throw new \Exception(sprintf('Please inject %s as an interface in %s (also see service.yaml).', $coachCalendarHelper, self::class));
                        }
                        $this->calendarHelpers[$coachCalendarHelper]->removeFromCalendar($event, $coach);
                    }
                }

                // Création de l'événement dans l'agenda du négociateur
                $mandataryCalendarHelper = $this->getMandataryCalendarHelper($mandatary);
                if (null !== $mandataryCalendarHelper) {
                    if (!isset($this->calendarHelpers[$mandataryCalendarHelper])) {
                        throw new \Exception(sprintf('Please inject %s as an interface in %s (also see service.yaml).', $mandataryCalendarHelper, self::class));
                    }

                    if ($mandataryCalendarHelper !== $coachCalendarHelper) { // Car déjà supprimer celui du coach qui est le même
                        $this->calendarHelpers[$mandataryCalendarHelper]->removeFromCalendar($event, $mandatary);
                    }
                }

                // Envoi d'un SMS au négociateur pour lui annoncer l'annulation du RDV à venir, sauf en mode dev
                if (!$this->isDev) {
                    $date = $event->getDate();
                    if (new \DateTime() < $date) {
                        $sent = false;
                        // Test sur le coach comme dans le hook à la suppression
                        if (null !== $coach) {
                            $content = $this->translator->trans(
                                "Bonjour %mandatary%.\nJe dois annuler notre rendez-vous du %date% à %time%. A bientot,\n%coach%",
                                [
                                    '%mandatary%' => $mandatary->getFirstName(),
                                    '%date%' => $date->format('d/m/Y'),
                                    '%time%' => $date->format('H:i'),
                                    '%coach%' => $coach,
                                ]
                            );
                            $sent = $this->smsHelper->send($mandatary, $content);
                        }
                        if ($sent) {
                            $this
                                ->session
                                ->getFlashBag()
                                ->add('success', $this->translator->trans("SMS d'annulation de rendez-vous envoyé à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                            ;
                        } else {
                            $this
                                ->session
                                ->getFlashBag()
                                ->add('error', $this->translator->trans("SMS d'annulation de rendez-vous non envoyé à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                            ;
                        }
                    }
                } else {
                    $this
                        ->session
                        ->getFlashBag()
                        ->add('notice', $this->translator->trans("Mode dev : pas d'envoi de SMS d'annulation de rendez-vous à %mandatary%.", ['%mandatary%' => (string) $mandatary]))
                    ;
                }
                break;
            case 'App\Entity\Event\CommentEvent':
                if ('pp' === $mandatary->getNetwork()) {
                    $this
                        ->session
                        ->getFlashBag()
                        ->add('warning', $this->translator->trans("Attention : la suppression d'un commentaire dans l'application n'entraîne pas sa suppression dans le CRM."))
                    ;
                }
                break;
        }
    }

    /**
     * Récupère le helper de gestion de l'agenda d'un coach.
     *
     * @param User\ImportableUserInterface $user
     *
     * @return string|null
     *
     * @throws \Exception
     */
    private function getCoachCalendarHelper(User\ImportableUserInterface $user): ?string
    {
        return $this->getCalendarHelper($user->getNetwork(), 'coach');
    }

    /**
     * Récupère le helper de gestion de l'agenda d'un négociateur.
     *
     * @param User\ImportableUserInterface $user
     *
     * @return string|null
     *
     * @throws \Exception
     */
    private function getMandataryCalendarHelper(User\ImportableUserInterface $user): ?string
    {
        return $this->getCalendarHelper($user->getNetwork(), 'mandatary');
    }

    /**
     * Récupère le helper de gestion de l'agenda de l'utilisateur,
     * en fonction du type d'agenda qu'il utilise.
     *
     * @param string $network
     * @param string $key
     *
     * @return string|null
     *
     * @throws \Exception Lorsque la clé n'est pas 'coach' ou 'mandatary'
     */
    private function getCalendarHelper(string $network, string $key): ?string
    {
        if (!in_array($key, ['coach', 'mandatary'])) {
            throw new \Exception('$key parameter must be "coach" or "mandatary".');
        }

        /**
         * Valeur de retour
         *
         * @var string|null
         */
        $calendarHelper = null;

        /**
         * L'agenda éventuel de l'utilisateur, par exemple 'google' ou 'zimbra'.
         *
         * @var string|null
         */
        $calendar = $this->config['networks'][$network]['calendars'][$key];

        if (null !== $calendar) {
            $calendarHelper = $this->config['calendars'][$calendar]['helper'];
        }

        return $calendarHelper;
    }
}
