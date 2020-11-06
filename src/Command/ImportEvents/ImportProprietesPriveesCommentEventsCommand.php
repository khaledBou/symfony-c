<?php

namespace App\Command\ImportEvents;

use App\Entity\Event\CommentEvent;
use App\Entity\User\Mandatary;
use App\Entity\User\Coach;
use App\Service\ProprietesPriveesApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande d'import des événements "Commentaire" des négociateurs Proprietes-Privees depuis le CRM.
 */
class ImportProprietesPriveesCommentEventsCommand extends Command
{
    /**
     * L'identifiant du réseau pour lequel importer les événements "Commentaire".
     *
     * @var string
     */
    const NETWORK = 'pp';

    /**
     * @var string
     */
    protected static $defaultName = 'app:event:comment:import:pp';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @param EntityManagerInterface     $em
     * @param ProprietesPriveesApiHelper $proprietesPriveesApiHelper
     */
    public function __construct(EntityManagerInterface $em, ProprietesPriveesApiHelper $proprietesPriveesApiHelper)
    {
        parent::__construct();

        $this->em = $em;
        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;

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
            ->setDescription('Importe les commentaires des négociateurs Proprietes-Privees depuis le CRM')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(sprintf('[%s] Début import commentaires', (new \DateTime())->format('d/m/Y H:i:s')));

        /**
         * Négociateurs classés par adresse e-mail.
         *
         * @var Mandatary[]
         */
        $mandatariesByEmail = [];
        $mandataries = $this->em->getRepository(Mandatary::class)->findBy([
            'network' => self::NETWORK,
            'enabled' => true,
        ]);
        foreach ($mandataries as $mandatary) {
            $email = $mandatary->getEmail();
            $mandatariesByEmail[$email] = $mandatary;
        }
        // Indique au garbage collector de prendre en charge cette variable
        unset($mandataries);

        /**
         * Coachs classés par adresse e-mail.
         *
         * @var Coach[]
         */
        $coaches = $this->em->getRepository(Coach::class)->findBy([
            'network' => self::NETWORK,
        ]);
        foreach ($coaches as $coach) {
            $email = $coach->getEmail();
            $coachesByEmail[$email] = $coach;
        }
        // Indique au garbage collector de prendre en charge cette variable
        unset($coaches);

        // Repository des événements "Commentaire"
        $commentEventRepository = $this->em->getRepository(CommentEvent::class);

        // Récupération des commentaires issus du CRM
        $apiOutput = $this->proprietesPriveesApiHelper->call('manage-mandatary', [
            // 1 jour est une valeur suffisante car la présente commande est exécutée plusieurs fois par jour
            'from_date' => (new \DateTime('-1 day'))->format('Y-m-d H:i:s'),
        ]);

        if (null !== $apiOutput && $apiOutput->success) {
            ProgressBar::setFormatDefinition('custom', '%message%'.PHP_EOL.'%current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s% %memory:6s%)'.PHP_EOL);

            $io->writeln('Import :');

            $progressBar = new ProgressBar($output, count((array) $apiOutput->data));
            $progressBar->setFormat('custom');

            // Indique si un flush est nécessaire (optimisation)
            $doFlush = false;

            // Force l'activation du garbage collector
            gc_enable();

            foreach ((array) $apiOutput->data as $mandataryEmail => $mandataryComments) {
                // @var Mandatary|null
                $mandatary = isset($mandatariesByEmail[$mandataryEmail]) ? $mandatariesByEmail[$mandataryEmail] : null;

                if (null !== $mandatary) {
                    foreach ($mandataryComments as $mandataryComment) {
                        // @var \DateTime
                        $date = new \DateTime($mandataryComment->date_creation); // @codingStandardsIgnoreLine

                        // @var Coach|null
                        $coach = isset($coachesByEmail[$mandataryComment->email_employe]) ? // @codingStandardsIgnoreLine
                                       $coachesByEmail[$mandataryComment->email_employe] : null; // @codingStandardsIgnoreLine

                        /**
                         * Avant la mise en place de l'application, les coachs laissaient des commentaires dans le CRM.
                         * À la livraison de l'application, le 11/03/2020, ils a été demandé aux coachs de faire une double saisie des commentaires
                         * dans le CRM et dans l'application car il n'existait pas de synchronisation des commentaires entre les deux plateformes.
                         *
                         * Le présent script doit donc éviter de récupérer les commentaires que les coachs ont laissé dans le CRM
                         * après la livraison de l'application, car les commentaires sont déjà censés être présents dans l'application.
                         *
                         * Une synchronisation à double sens a été déployée le 22/04/2020, date à partir de laquelle les coachs
                         * ont eu pour consigne de ne plus recopier leurs commentaires dans le CRM.
                         */
                        if (null === $coach || $date < new \DateTime('2020-03-11')) {
                            // @var string|null
                            $initiator = null === $coach ? trim($mandataryComment->prenom_nom_employe) : null; // @codingStandardsIgnoreLine

                            // @var string
                            $comment = $mandataryComment->commentaire;

                            $commentEvent = $commentEventRepository->findOneBy([
                                'mandatary' => $mandatary,
                                'coach' => $coach,
                                'initiator' => $initiator,
                                'date' => $date,
                                'comment' => $comment,
                            ]);

                            if (null === $commentEvent) {
                                $commentEvent = (new CommentEvent())
                                    ->setMandatary($mandatary)
                                    ->setCoach($coach)
                                    ->setInitiator($initiator)
                                    ->setDate($date)
                                    ->setComment($comment)
                                ;

                                $this->em->persist($commentEvent);
                                $doFlush = true;

                                // Indique au garbage collector de prendre en charge ces objets
                                unset($commentEvent);
                                unset($coach);
                            }
                        }
                    }

                    // Indique au garbage collector de prendre en charge cet objet
                    unset($mandatariesByEmail[$mandataryEmail]);
                }

                $progressBar->setMessage((string) $mandatary);
                $progressBar->advance();

                if (0 === $progressBar->getProgress() % 100) {
                    if ($doFlush) {
                        $this->em->flush();
                        $doFlush = false;
                    }

                    // Force l'appel du garbage collector
                    gc_collect_cycles();
                }
            }

            if ($doFlush) {
                $this->em->flush();
            }

            // Force l'appel du garbage collector
            gc_collect_cycles();

            $progressBar->finish();
            $io->newLine();
        } else {
            throw new \Exception(
                null === $apiOutput ?
                'Could not get comments from API.' :
                sprintf('Could not get comments from API: %s', $apiOutput->message)
            );
        }

        $io->title(sprintf('[%s] Fin import commentaires', (new \DateTime())->format('d/m/Y H:i:s')));

        return 0;
    }
}
