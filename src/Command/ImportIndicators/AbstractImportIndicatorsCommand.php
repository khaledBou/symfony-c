<?php

namespace App\Command\ImportIndicators;

use App\Entity\Indicator;
use App\Entity\User\Mandatary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'import des indicateurs.
 */
abstract class AbstractImportIndicatorsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag)
    {
        // Avant tout
        $this->checkForNetworkConstant();

        parent::__construct();

        $this->em = $em;
        $this->config = $parameterBag->get('app');

        // Désactivation des logs Doctrine pour réduire la consommation de mémoire
        $em
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger(null)
        ;
    }

    /**
     * Construit, récupère ou met à jour les indicateurs du négociateur.
     *
     * @param Mandatary $mandatary Le négociateur
     *
     * @return IndicatorInterface[]
     */
    public function getIndicators(Mandatary $mandatary): array
    {
        /**
         * Les indicateurs classés par clés.
         *
         * @var Indicator\IndicatorInterface[]
         */
        $indicators = [];

        // Récupération
        foreach ($this
            ->em
            ->getRepository(Indicator\AbstractIndicator::class)
            ->findByMandatary($mandatary) as $indicator) {
            $indicatorKey = $indicator->getKey();
            $indicators[$indicatorKey] = $indicator;
        }

        // Construction et mise à jour
        foreach ($this->config['indicators'] as $indicatorKey => $indicatorConfig) {
            // Si l'indicateur concerne le réseau
            if (in_array($this::NETWORK, $indicatorConfig['networks'])) {
                // Récupération ou création
                if (isset($indicators[$indicatorKey])) {
                    $indicator = $indicators[$indicatorKey];
                } else {
                    $indicatorEntity = $indicatorConfig['entity'];
                    $indicator = new $indicatorEntity();
                }

                $indicator
                    ->setMandatary($mandatary)
                    ->setKey($indicatorKey)
                ;

                // Appelle la méthode de remplissage éventuelle
                $fillMethod = $indicatorConfig['fill_method'];
                if (null !== $fillMethod) {
                    $indicator = $this->$fillMethod($indicator);
                }

                $indicators[$indicatorKey] = $indicator;
            }
        }

        return $indicators;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        throw new \LogicException('The configure() method must be overriden in the class implementation.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début import indicateurs', (new \DateTime())->format('d/m/Y H:i:s')));

        $mandataries = $this
            ->em
            ->getRepository(Mandatary::class)
            ->findBy([
                'enabled' => true,
                'network' => $this::NETWORK,
            ])
        ;

        if ($mandataries) {
            ProgressBar::setFormatDefinition('custom', '%message%'.PHP_EOL.'%current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s% %memory:6s%)'.PHP_EOL);

            $io->writeln('Import :');

            $progressBar = new ProgressBar($output, count($mandataries));
            $progressBar->setFormat('custom');

            // Force l'activation du garbage collector
            gc_enable();

            foreach ($mandataries as $key => $mandatary) {
                // Indicateurs
                $indicators = $this->getIndicators($mandatary);
                foreach ($indicators as $indicator) {
                    $mandatary->addIndicator($indicator);
                }

                $this->em->persist($mandatary);

                // Flush tous les 100 négociateurs
                if (0 === $key % 100) {
                    $this->em->flush();

                    // Force l'appel du garbage collector
                    gc_collect_cycles();
                }

                $progressBar->setMessage((string) $mandatary);
                $progressBar->advance();

                // Indique au garbage collector de prendre en charge cet objet
                unset($mandatary);
            }

            $progressBar->finish();
            $io->newLine();

            $this->em->flush();

            // Force l'appel du garbage collector
            gc_collect_cycles();
        }

        $io->title(sprintf('[%s] Fin import indicateurs', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }

    /**
     * Vérifie que l'implémentation de la classe définit bien la constante NETWORK,
     * désignant l'identifiant du réseau pour lequel importer les indicateurs.
     *
     * @return void
     */
    private function checkForNetworkConstant(): void
    {
        if (!defined(sprintf('%s::NETWORK', get_class($this)))) {
            throw new \LogicException('The NETWORK constant must be defined in the class implementation.');
        }
    }
}
