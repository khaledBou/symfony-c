<?php

namespace App\Service\Calendar;

use App\Entity\Event\AppointmentEvent;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zimbra\Enum\AccountBy;
use Zimbra\Enum\AddressType;
use Zimbra\Enum\AlarmAction;
use Zimbra\Enum\FreeBusyStatus;
use Zimbra\Enum\InviteClass;
use Zimbra\Enum\InviteStatus;
use Zimbra\Enum\Transparency;
use Zimbra\Mail\MailFactory;
use Zimbra\Mail\Struct\AlarmInfo;
use Zimbra\Mail\Struct\AlarmTriggerInfo;
use Zimbra\Mail\Struct\DtTimeInfo;
use Zimbra\Mail\Struct\DurationInfo;
use Zimbra\Mail\Struct\EmailAddrInfo;
use Zimbra\Mail\Struct\InvitationInfo;
use Zimbra\Mail\Struct\InviteComponent;
use Zimbra\Mail\Struct\MimePartInfo;
use Zimbra\Mail\Struct\Msg;
use Zimbra\Struct\AccountSelector;
use Zimbra\Soap\Response;

/**
 * Helper de gestion de l'agenda Zimbra.
 */
class ZimbraCalendarHelper extends AbstractCalendarHelper
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param EntityManagerInterface $em
     * @param SessionInterface       $session
     * @param TranslatorInterface    $translator
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(EntityManagerInterface $em, SessionInterface $session, TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        parent::__construct($em, $session, $translator);

        $this->config = $parameterBag->get('app');
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');
    }

    /**
     * @see https://github.com/zimbra-api/zimbra-api/issues/11#issuecomment-245861141
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

        // Le réseau de l'utilisateur
        $network = $user->getNetwork();

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
            $name = sprintf("%s (avec %s)", $event->getSubject(), $attendee);
        } else {
            $name = $event->getSubject();
        }

        $created = false;

        try {
            // Connexion à l'API
            $mailAPI = MailFactory::instance($this->config['networks'][$network]['zimbra_api_url']);
            $email = $user->getEmail();
            $password = $user->getZimbraPassword();
            $mailAPI->auth(new AccountSelector(AccountBy::NAME(), $email), $password);

            // Rappel
            $trigger = new AlarmTriggerInfo();
            $trigger->setRelative(new DurationInfo(true, null, 1, null, null, null, 'START'));
            $alarm = new AlarmInfo(AlarmAction::DISPLAY(), $trigger);

            // L'évènement
            $comp = new InviteComponent();
            $comp
                ->setName($name)
                ->setFreeBusy(FreeBusyStatus::BUSY())
                ->setStatus(InviteStatus::COMPLETED())
                ->setCalClass(InviteClass::PUB())
                ->setTransparency(Transparency::OPAQUE())
                ->setIsAllDay(false)
                ->setIsDraft(false)
                ->setDtStart(new DtTimeInfo($startDate->format('Ymd\THis'), 'Europe/Paris'))
                ->setDtEnd(new DtTimeInfo($endDate->format('Ymd\THis'), 'Europe/Paris'))
                ->addAlarm($alarm)
            ;

            // Description éventuelle
            $description = $event->getDescription();
            if (null !== $description) {
                $comp->setDescription($description);
            }

            // L'invitation
            $inv = new InvitationInfo();
            $inv->setInviteComponent($comp);

            // Le mail
            $mp = new MimePartInfo(null, 'multipart/alternative');
            $mp->addMimePart(new MimePartInfo(null, 'text/plain', $event->getDescription()));
            $msg = new Msg();
            $msg
                ->setSubject($event->getSubject())
                ->addEmail(new EmailAddrInfo($email, AddressType::TO(), (string) $user))
                ->setInvite($inv)
                ->setMimePart($mp)
            ;

            /**
             * Appel de l'API.
             *
             * @var Response
             */
            $response = $mailAPI->createAppointment($msg);

            /**
             * Identifiant de l'événement Zimbra.
             *
             * @var string
             */
            $zimbraCalendarId = $response->__get('invId');

            $this
                ->getSession()
                ->getFlashBag()
                ->add('success', $this->getTranslator()->trans("Événement créé dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;

            // Enregistre l'identifiant de l'événement Zimbra
            $event->setZimbraCalendarId($zimbraCalendarId);
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();

            $created = true;
        } catch (\Exception $e) {
            $this
                ->getSession()
                ->getFlashBag()
                ->add('error', $this->getTranslator()->trans("Impossible de créer l'événement dans l'agenda de %user%.", ['%user%' => (string) $user]))
            ;
        }

        return $created;
    }

    /**
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
        $zimbraCalendarId = $event->getZimbraCalendarId();

        if (null !== $zimbraCalendarId) {
            // Le réseau de l'utilisateur
            $network = $user->getNetwork();

            try {
                // Connexion à l'API
                $mailAPI = MailFactory::instance($this->config['networks'][$network]['zimbra_api_url']);
                $email = $user->getEmail();
                $password = $user->getZimbraPassword();
                $mailAPI->auth(new AccountSelector(AccountBy::NAME(), $email), $password);

                /**
                 * Appel de l'API.
                 *
                 * @var Response
                 */
                $mailAPI->cancelAppointment(null, null, null, $zimbraCalendarId, 0);

                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('success', $this->getTranslator()->trans("Événement supprimé de l'agenda de %user%.", ['%user%' => (string) $user]))
                ;
            } catch (\Exception $e) {
                $this
                    ->getSession()
                    ->getFlashBag()
                    ->add('notice', $this->getTranslator()->trans("Événement déjà supprimé de l'agenda de %user%.", ['%user%' => (string) $user]))
                ;
            }

            // Supprime l'identifiant de l'événement Zimbra
            $event->setZimbraCalendarId(null);
            $this->getEntityManager()->persist($event);
            $this->getEntityManager()->flush();

            $removed = true;
        }

        return $removed;
    }
}
