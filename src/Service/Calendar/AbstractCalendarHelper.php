<?php

namespace App\Service\Calendar;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Helper parent de gestion des agendas,
 * fournit l'entity manager, la session et le traducteur.
 */
abstract class AbstractCalendarHelper implements CalendarHelperInterface
{
    /**
     * @param EntityManagerInterface
     */
    private $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param EntityManagerInterface $em
     * @param SessionInterface       $session
     * @param TranslatorInterface    $translator
     */
    public function __construct(EntityManagerInterface $em, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}
