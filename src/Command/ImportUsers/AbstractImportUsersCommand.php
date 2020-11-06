<?php

namespace App\Command\ImportUsers;

use App\Entity\Event\AbstractEvent;
use App\Entity\User;
use App\Service\MandataryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'import des utilisateurs.
 */
abstract class AbstractImportUsersCommand extends Command implements ImportUsersCommandInterface
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
     * @var MandataryHelper
     */
    protected $mandataryHelper;

    /**
     * Nombre d'utilisateurs créés.
     *
     * @var int
     */
    private $createdUsersCount = 0;

    /**
     * Nombre d'utilisateurs mis à jour.
     *
     * @var int
     */
    private $updatedUsersCount = 0;

    /**
     * Nombre d'utilisateurs supprimés.
     *
     * @var int
     */
    private $disabledUsersCount = 0;

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     * @param MandataryHelper        $mandataryHelper
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, MandataryHelper $mandataryHelper)
    {
        // Avant tout
        $this->checkForNetworkConstant();
        $this->checkForUserClassConstant();

        parent::__construct();

        $this->em = $em;
        $this->config = $parameterBag->get('app');
        $this->mandataryHelper = $mandataryHelper;

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
        throw new \LogicException('The configure() method must be overriden in the class implementation.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début import utilisateurs', (new \DateTime())->format('d/m/Y H:i:s')));

        // Hook pré-import
        $io->writeln(sprintf('[%s] Début hook pré-import', (new \DateTime())->format('d/m/Y H:i:s')));
        $this->preImportHook();
        $io->writeln(sprintf('[%s] Fin hook pré-import', (new \DateTime())->format('d/m/Y H:i:s')));
        $io->newLine();

        // Les utilisateurs déjà importés dans l'application
        $users = $this->em->getRepository(User\AbstractUser::class)->findImportedUsers($this::USER_CLASS, $this::NETWORK);

        // Les utilisateurs supprimés à désactiver à la fin de l'import
        $usersToDisable = [];
        foreach ($users as $user) {
            $email = $user->getEmail();
            $usersToDisable[$email] = $user;
        }

        // Récupère les utilisateurs depuis la source de données
        $io->writeln(sprintf('[%s] Début de récupération des utilisateurs depuis la source de données', (new \DateTime())->format('d/m/Y H:i:s')));
        $usersData = $this->getUsersData();
        $io->writeln(sprintf('[%s] Fin de récupération des utilisateurs depuis la source de données', (new \DateTime())->format('d/m/Y H:i:s')));
        $io->newLine();

        if ($usersData) {
            ProgressBar::setFormatDefinition('custom', '%message%'.PHP_EOL.'%current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s% %memory:6s%)'.PHP_EOL);

            $io->writeln('Import :');

            $progressBar = new ProgressBar($output, count($usersData));
            $progressBar->setFormat('custom');

            // Force l'activation du garbage collector
            gc_enable();

            foreach ($usersData as $key => $userData) {
                // Tente de récupérer l'utilisateur dans la base de données
                $user = $this->getUserFromRepository($userData);

                // Création ou mise à jour
                if (null === $user) {
                    $userClass = $this::USER_CLASS;
                    $user = new $userClass();
                    $this->createdUsersCount++;
                } else {
                    $this->updatedUsersCount++;
                }

                // Historisation des données importées
                $previouslyImportedData = $user->getCurrentlyImportedData();
                $currentlyImportedData = (array) $userData;
                $user
                    ->setPreviouslyImportedData($previouslyImportedData)
                    ->setCurrentlyImportedData($currentlyImportedData)
                ;

                // Remplit les attributs de l'utilisateur
                $user = $this->setUserAttributes($user, $userData);

                /* Cas particuliers selon le type d'utilisateur
                 * mais universels à tous les réseaux. */
                switch ($this->em->getMetadataFactory()->getMetadataFor(get_class($user))->getName()) {
                    case 'App\Entity\User\Mandatary':
                        /* On va retrouver ici des informations calculées mais stockées sur le négociateur,
                         * de façon à éviter un calcul à la volée. */
                        $user
                            /* Définit le statut de suivi.
                             * Le fait de conditionner sur l'existence du négociateur en base de données est une contrainte Doctrine :
                             * le négociateur doit déjà être persisté pour l'utiliser dans les requêtes repository. */
                            ->setSupportStatus(null !== $user->getId() ? $this->mandataryHelper->getSupportStatus($user) : User\Mandatary::SUPPORT_STATUS_BAD)
                            // Indique si le négociateur est autonome en publication
                            ->setAutonomePublication($this->mandataryHelper->isAutonomePublication($user))
                            // Indique si le négociateur est suspendu ou résilié
                            ->setSuspendedOrResigned($this->mandataryHelper->isSuspendedOrResigned($user))
                            // Indique si le négociateur ne rentre plus de mandats
                            ->setTradeShortfall($this->mandataryHelper->hasTradeShortfall($user))
                            // Indique si le négociateur ne signe plus de compromis
                            ->setCompromiseShortfall($this->mandataryHelper->hasCompromiseShortfall($user))
                            // Indique si le négociateur ne fait plus de ventes
                            ->setSaleShortfall($this->mandataryHelper->hasSaleShortfall($user))
                            // Compte le nombre de contacts avec le coach
                            ->setContactsCount($user->getId() ? $this->em->getRepository(AbstractEvent::class)->countContacts($user) : 0)
                        ;

                        break;
                }

                $this->em->persist($user);

                // Flush tous les 100 utilisateurs
                if (0 === $key % 100) {
                    $this->em->flush();

                    // Force l'appel du garbage collector
                    gc_collect_cycles();
                }

                $progressBar->setMessage((string) $user);
                $progressBar->advance();

                $email = $user->getEmail();
                unset($usersToDisable[$email]);

                // Indique au garbage collector de prendre en charge ces objets
                unset($user);
                unset($userData);
            }

            $progressBar->finish();
            $io->newLine();

            $this->em->flush();

            // Force l'appel du garbage collector
            gc_collect_cycles();

            if ($usersToDisable) {
                $io->writeln('Utilisateurs à désactiver :');

                $progressBar = new ProgressBar($output, count($usersToDisable));
                $progressBar->setFormat('custom');

                foreach ($usersToDisable as $userToDisable) {
                    if ($userToDisable->isEnabled()) {
                        $this->disabledUsersCount++;

                        $userToDisable->setEnabled(false);
                        $this->em->persist($userToDisable);
                    }
                    $progressBar->setMessage((string) $userToDisable);
                    $progressBar->advance();
                }

                $progressBar->finish();
                $io->newLine();
            }

            $this->em->flush();

            // Force l'appel du garbage collector
            gc_collect_cycles();
        }

        // Hook post-import
        $io->writeln(sprintf('[%s] Début hook post-import', (new \DateTime())->format('d/m/Y H:i:s')));
        $this->postImportHook();
        $io->writeln(sprintf('[%s] Fin hook post-import', (new \DateTime())->format('d/m/Y H:i:s')));
        $io->newLine();

        // Feedback
        $io->writeln('Nombre d\'utilisateurs :');
        $io->listing([
            sprintf('créés : %s%d%s', $this->createdUsersCount > 0 ? '<info>' : '', $this->createdUsersCount, $this->createdUsersCount > 0 ? '</info>' : ''),
            sprintf('mis à jour : %s%d%s', $this->updatedUsersCount > 0 ? '<info>' : '', $this->updatedUsersCount, $this->updatedUsersCount > 0 ? '</info>' : ''),
            sprintf('désactivés : %s%d%s', $this->disabledUsersCount > 0 ? '<info>' : '', $this->disabledUsersCount, $this->disabledUsersCount > 0 ? '</info>' : ''),
        ]);

        $io->title(sprintf('[%s] Fin import utilisateurs', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }

    /**
     * Hook pré-import.
     *
     * @return void
     */
    protected function preImportHook(): void
    {
    }

    /**
     * Hook post-import.
     *
     * @return void
     */
    protected function postImportHook(): void
    {
    }

    /**
     * Vérifie que l'implémentation de la classe définit bien la constante NETWORK,
     * désignant l'identifiant du réseau pour lequel importer les utilisateurs.
     *
     * @return void
     */
    private function checkForNetworkConstant(): void
    {
        if (!defined(sprintf('%s::NETWORK', get_class($this)))) {
            throw new \LogicException('The NETWORK constant must be defined in the class implementation.');
        }
    }

    /**
     * Vérifie que l'implémentation de la classe définit bien la constante USER_CLASS,
     * désignant le fully-qualified name de la classe utilisateur.
     *
     * @return void
     */
    private function checkForUserClassConstant(): void
    {
        if (!defined(sprintf('%s::USER_CLASS', get_class($this)))) {
            throw new \LogicException('The USER_CLASS constant must be defined in the class implementation.');
        }
    }
}
