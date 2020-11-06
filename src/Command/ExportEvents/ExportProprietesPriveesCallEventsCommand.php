<?php

namespace App\Command\ExportEvents;

use App\Entity\Event\CallEvent;
use App\Service\ProprietesPriveesApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Commande d'export one-shot des événements "Appel téléphonique" des négociateurs Proprietes-Privees vers le CRM.
 *
 * N'est destinée à être exécutée qu'une fois au déploiement de la fonctionnalité d'envoi des événements "Appel téléphonique" vers le CRM,
 * puisque la synchronisation de ces événements se fera ensuite à la volée à chaque création d'un événement.
 */
class ExportProprietesPriveesCallEventsCommand extends Command
{
    /**
     * Sécurité pour empêcher l'exécution non intentionnelle.
     *
     * @var bool
     */
    const DO_EXECUTE = false;

    /**
     * L'identifiant du réseau pour lequel export les événements "Appel téléphonique".
     *
     * @var string
     */
    const NETWORK = 'pp';

    /**
     * @var string
     */
    protected static $defaultName = 'app:event:call:export:pp';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $isDev;

    /**
     * @param EntityManagerInterface     $em
     * @param ProprietesPriveesApiHelper $proprietesPriveesApiHelper
     * @param TranslatorInterface        $translator
     * @param ParameterBagInterface      $parameterBag
     */
    public function __construct(EntityManagerInterface $em, ProprietesPriveesApiHelper $proprietesPriveesApiHelper, TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        parent::__construct();

        $this->em = $em;
        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;
        $this->translator = $translator;
        $this->isDev = 'dev' === $parameterBag->get('kernel.environment');

        // Désactivation des logs Doctrine pour réduire la consommation de mémoire
        $em
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null)
        ;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Exporte les appels téléphoniques des négociateurs Proprietes-Privees vers le CRM')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début export appels téléphoniques', (new \DateTime())->format('d/m/Y H:i:s')));

        if (self::DO_EXECUTE && !$this->isDev) {
            // Les événements "appel téléphonique"
            $events = $this
                ->em
                ->getRepository(CallEvent::class)
                ->findAllByNetwork(self::NETWORK)
            ;

            $io->writeln('Export :');

            $progressBar = new ProgressBar($output, count($events));

            foreach ($events as $event) {
                $mandatary = $event->getMandatary();
                $coach = null !== $event->getCoach() ? $event->getCoach() : $mandatary->getCoach();
                $this->proprietesPriveesApiHelper->call('manage-mandatary', [
                    'mandatary_email' => $mandatary->getEmail(),
                    'employee_email' => $coach->getEmail(),
                    'date' => $event->getDate()->format('Y-m-d H:i:s'),
                    'message' => $event->isIncoming() ?
                        $this->translator->trans("Appel entrant :\n\n%report%.", ['%report%' => $event->getReport()]) :
                        $this->translator->trans("Appel sortant :\n\n%report%.", ['%report%' => $event->getReport()]),
                ], 'POST');

                $progressBar->advance();
            }
        } else {
            $io->writeln('Mode dev ou constante DO_EXECUTE à false : aucun appel téléphonique ne sera exporté vers le CRM.');
        }

        $io->title(sprintf('[%s] Fin export appels téléphoniques', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
