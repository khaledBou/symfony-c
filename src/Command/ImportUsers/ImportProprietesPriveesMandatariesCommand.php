<?php

namespace App\Command\ImportUsers;

use App\Entity\User\ImportableUserInterface;
use App\Entity\User\Coach;
use App\Entity\User\Mandatary;
use App\Service\FileUploader;
use App\Service\GdeApiHelper;
use App\Service\MandataryHelper;
use App\Service\NotificationHelper;
use App\Service\ProprietesPriveesApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Commande d'import des négociateurs Proprietes-Privees.
 *
 * Importe les négociateurs depuis l'API du CRM Proprietes-Privees.
 * Cette commande gère la mise à jour des négociateurs et peut donc être jouée plusieurs fois.
 */
class ImportProprietesPriveesMandatariesCommand extends AbstractImportUsersCommand
{
    /**
     * Le fully-qualified name de la classe utilisateur.
     *
     * @var string
     */
    const USER_CLASS = Mandatary::class;

    /**
     * L'identifiant du réseau pour lequel importer les négociateurs.
     *
     * @var string
     */
    const NETWORK = 'pp';

    /**
     * Correspondance entre les contrats renvoyés par l'API et ceux de l'application.
     *
     * @var array
     */
    const CONTRACTS = [
        'AUTO ENTREPRENEUR' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'AGENT COMMERCIAL (TNS)' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'PORTAGE' => Mandatary::CONTRACT_PORTAGE_SALARIAL,
        'GÉRANT MAJORITAIRE' => Mandatary::CONTRACT_CONCESSIONNAIRE,
    ];

    /**
     * @var string
     */
    protected static $defaultName = 'app:user:import:mandataries:pp';

    /**
     * @var GdeApiHelper
     */
    private $gdeApiHelper;

    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * Missions de formation Booster réalisées par les négociateurs.
     *
     * @var array
     */
    private $boosterCompletion = [];

    /**
     * Identifiants Freshdesk des négociateurs.
     */
    private $freshdeskUserIds = [];

    /**
     * Évaluations Freshdesk, tous négociateurs confondus.
     *
     * @var array
     */
    private $freshdeskFeedbacks = [];

    /**
     * @param EntityManagerInterface     $em
     * @param ParameterBagInterface      $parameterBag
     * @param MandataryHelper            $mandataryHelper
     * @param GdeApiHelper               $gdeApiHelper
     * @param ProprietesPriveesApiHelper $proprietesPriveesApiHelper
     * @param NotificationHelper         $notificationHelper
     * @param FileUploader               $fileUploader
     * @param TranslatorInterface        $translator
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, MandataryHelper $mandataryHelper, GdeApiHelper $gdeApiHelper, ProprietesPriveesApiHelper $proprietesPriveesApiHelper, NotificationHelper $notificationHelper, FileUploader $fileUploader, TranslatorInterface $translator)
    {
        parent::__construct($em, $parameterBag, $mandataryHelper);

        $this->gdeApiHelper = $gdeApiHelper;
        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;
        $this->notificationHelper = $notificationHelper;
        $this->fileUploader = $fileUploader;
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function getUserFromRepository($userData): ?ImportableUserInterface
    {
        return $this
            ->em
            ->getRepository(self::USER_CLASS)
            ->findOneBy(['email' => $userData->email_professionnel]) // @codingStandardsIgnoreLine
        ;
    }

    /**
     * @inheritdoc
     */
    public function getUsersData(): array
    {
        $output = $this->proprietesPriveesApiHelper->call('coaching', []);

        $usersData = [];

        if (null !== $output && $output->success) {
            foreach ($output->data as $userData) {
                // @codingStandardsIgnoreStart
                // Ne considère que les négociateurs actifs ayant une adresse email
                if ($userData->actif && $userData->email_professionnel) {
                    $email = $userData->email_professionnel;

                    // Injection des missions de formation Booster réalisées par ce négociateur
                    $userData->boosterCompletion = isset($this->boosterCompletion->{$email}) ?
                                                         $this->boosterCompletion->{$email} : [];

                    // Injection de l'identifiant Freshdesk du négociateur
                    $freshdeskUserId = isset($this->freshdeskUserIds[$email]) ?
                                             $this->freshdeskUserIds[$email] : null;
                    $userData->freshdeskUserId = $freshdeskUserId;

                    // Injection des évaluations Freshdesk laissées par ce négociateur
                    $userData->freshdeskFeedbacks = isset($this->freshdeskFeedbacks[$freshdeskUserId]) ?
                                                          $this->freshdeskFeedbacks[$freshdeskUserId] : [];

                    $usersData[] = $userData;
                }
                // @codingStandardsIgnoreEnd
            }
        } else {
            throw new \Exception(
                null === $output ?
                'Could not get mandataries from API.' :
                sprintf('Could not get mandataries from API: %s (%s)', $output->reason, $output->detail)
            );
        }

        return $usersData;
    }

    /**
     * @inheritdoc
     */
    public function setUserAttributes(ImportableUserInterface $user, $userData): ImportableUserInterface
    {
        // Activités
        $activities = [];
        $rawActivities = null !== $userData->activites ? json_decode($userData->activites) : [];
        foreach ($rawActivities as $rawActivity) {
            $activities[] = $rawActivity->name;
        }

        // Dates des mandats
        $tradesDates = [];
        $rawTradesDates = null !== $userData->dates_mandats ? json_decode($userData->dates_mandats) : []; // @codingStandardsIgnoreLine
        foreach ($rawTradesDates as $rawTradeDate) {
            $tradesDates[] = new \DateTime($rawTradeDate);
        }
        // Nombre de mandats
        $tradesCount = null === $userData->nb_mandats ? 0 : $userData->nb_mandats; // @codingStandardsIgnoreLine

        // Dates des compromis
        $compromisesDates = [];
        $rawCompromisesDates = null !== $userData->dates_compromis ? json_decode($userData->dates_compromis) : []; // @codingStandardsIgnoreLine
        foreach ($rawCompromisesDates as $rawCompromiseDate) {
            $compromisesDates[] = new \DateTime($rawCompromiseDate);
        }
        // Nombre de compromis
        $compromisesCount = null === $userData->nb_compromis ? 0 : $userData->nb_compromis; // @codingStandardsIgnoreLine

        // Dates des ventes
        $salesDates = [];
        $rawSalesDate = null !== $userData->dates_ventes ? json_decode($userData->dates_ventes) : []; // @codingStandardsIgnoreLine
        foreach ($rawSalesDate as $rawSaleDate) {
            $salesDates[] = new \DateTime($rawSaleDate);
        }

        // Honoraires hors taxes
        $feeExclTax = [];
        $rawFeeExclTaxs = null !== $userData->honoraires_ventes ? json_decode($userData->honoraires_ventes) : []; // @codingStandardsIgnoreLine
        foreach ($rawFeeExclTaxs as $rawFeeExclTax) {
            $feeExclTax[] = $rawFeeExclTax;
        }

        // Nombre de ventes
        $salesCount = null === $userData->nb_ventes ? 0 : $userData->nb_ventes; // @codingStandardsIgnoreLine

        /**
         * Le coach qui est affecté au négociateur.
         *
         * @var Coach|null
         */
        $previousCoach = $user->getCoach();

        /**
         * Le coach qui sera, plus bas, affecté au négociateur.
         *
         * @var Coach|null
         */
        $coach = null !== $userData->coach ? $this->getCoach($userData->coach) : null;

        // Les années de début d'activité, N-1 et N-2
        $beginYear = (int) (new \DateTime($userData->date_entree))->format('Y'); // @codingStandardsIgnoreLine
        $yearN1 = (int) (new \DateTime('-1 year'))->format('Y');
        $yearN2 = (int) (new \DateTime('-2 year'))->format('Y');

        $user
            // @codingStandardsIgnoreStart
            ->setEmail($userData->email_professionnel)
            ->setNetwork(self::NETWORK)
            ->setImported(true)
            ->setEnabled(true)
            ->setFirstName($userData->prenom)
            ->setLastName($userData->nom)
            ->setSlug(strstr($userData->email_professionnel, '@', true))
            ->setCivility($userData->civilite)
            ->setPhone($userData->mobile ? $userData->mobile : $userData->telephone)
            ->setBirthDate(new \DateTime($userData->date_naissance))
            ->setBeginDate(new \DateTime($userData->date_entree))
            ->setTerminationDate(null !== $userData->date_sortie ? new \DateTime($userData->date_sortie) : null)
            ->setZipCode($userData->code_postal_secteur)
            ->setCity($userData->ville_secteur)
            ->setBareme(null !== $userData->bareme ? $userData->bareme : '')
            ->setContract(isset(self::CONTRACTS[$userData->regime]) ? self::CONTRACTS[$userData->regime] : null)
            ->setActivities($activities)
            ->setPack($userData->pack)
            ->setCrmUrl($userData->fiche_crm)
            ->setWebsiteUrl($userData->mini_site)
            ->setFreshdeskUserId($userData->freshdeskUserId)
            ->setAnimator(null !== $userData->animateur ? $this->getMandatary($userData->animateur) : null)
            ->setCoach($coach)
            ->setSalesRevenueHistory([
                $userData->ca_n,
                $yearN1 >= $beginYear ? $userData->ca_n_1 : null,
                $yearN2 >= $beginYear ? $userData->ca_n_2 : null,
            ])
            ->setSalesRevenue($userData->ca)
            ->setCrmLoginsCount($userData->nb_connexions)
            ->setLastCrmLoginDate(null !== $userData->date_derniere_connexion ? new \DateTime($userData->date_derniere_connexion) : null)
            ->setTradesDates($tradesDates)
            ->setTradesCount($tradesCount)
            ->setExclusiveTradesCount(null === $userData->nb_mandats_exclusifs ? 0 : $userData->nb_mandats_exclusifs)
            ->setCompromisesDates($compromisesDates)
            ->setCompromisesCount($compromisesCount)
            ->setSalesDates($salesDates)
            ->setSalesCount($salesCount)
            ->setZimbraPassword($userData->password)
            ->setFeeExclTax($rawFeeExclTaxs)
            // @codingStandardsIgnoreEnd
        ;

        // Tutorat (programme Tremplin)
        if (null !== $userData->tuteur) {
            $tutoring = json_decode($userData->tuteur);
            $user
                // @codingStandardsIgnoreStart
                ->setTutor($this->getMandatary($tutoring->professional_email_address)) // peut être null si le tuteur n'a pas encore été importé
                ->setTutoringStartDate(new \DateTime($tutoring->start_date))
                ->setTutoringEndDate(new \DateTime($tutoring->end_date))
                // @codingStandardsIgnoreEnd
            ;
        } else {
            $user
                ->setTutor(null)
                ->setTutoringStartDate(null)
                ->setTutoringEndDate(null)
            ;
        }

        // Avatar
        if (!empty($userData->photo)) {
            $upload = true;

            $url = $userData->photo; // l'URL de l'avatar
            $filename = $user->getAvatar(); // l'avatar déjà présent

            // Dans le cas où un avatar est déjà présent, teste s'il faut le mettre à jour
            if (null !== $filename && $this->fileUploader->exists($filename, 'avatar')) {
                $filepath = $this->fileUploader->getFilepath($filename, 'avatar');
                try {
                    if (sha1_file($url) === sha1_file($filepath)) { // fichiers identiques, pas de mise à jour
                        $upload = false;
                    } else { // fichiers différents, la mise à jour aura lieu après la suppression de l'ancien fichier
                        $this->fileUploader->delete($filename, 'avatar');
                    }
                } catch (\Exception $e) {
                    $upload = false;
                }
            }

            if ($upload) {
                $filename = $this->fileUploader->uploadByUrl($url, 'avatar');
                if (false === $filename) {
                    $filename = null;
                }
            }

            $user->setAvatar($filename);
        }

        /* Bien que cette opération soit normalement réalisée juste après
         * par la classe parente AbstractImportUsersCommand,
         * on persiste dès à présent le négociateur car les notifications
         * qui peuvent être créées ci-après auront besoin d'être attachées
         * à un négociateur persisté dans la base de données. */
        $this->em->persist($user);

        // Notifie le coach lorsqu'un négociateur lui est affecté
        if (null !== $coach && $coach !== $previousCoach) {
            $message = $this->translator->trans("Vous coachez désormais %mandatary%", [
                '%mandatary%' => $user->getFirstName(),
            ]);
            $this->notificationHelper->addMandataryNotification($user, $message);
        }

        // Notifie le coach lorsqu'un négociateur a bien plus de mandats que de ventes
        if ($salesCount > 0) {
            $salesRatio = $tradesCount / $salesCount;
            if ($salesRatio >= 15) {
                $message = $this->translator->trans("Ce négociateur a %ratio% fois plus de mandats que de ventes.", [
                    '%ratio%' => floor($salesRatio),
                ]);
                $this->notificationHelper->addMandataryNotification($user, $message);
            }
        } elseif ($tradesCount >= 15) {
            $message = $this->translator->trans("Ce négociateur a %count% mandats mais n'a fait aucune vente.", [
                '%count%' => $tradesCount,
            ]);
            $this->notificationHelper->addMandataryNotification($user, $message);
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import des négociateurs Proprietes-Privees')
            ->setHelp('Importe les négociateurs depuis l\'API du CRM Proprietes-Privees')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function preImportHook(): void
    {
        // Missions de formation Booster réalisées par les négociateurs
        $boosterCompletion = $this->gdeApiHelper->call('training-program-completion/booster', []);
        if (null === $boosterCompletion) {
            throw new \Exception(sprintf('Could not get Booster training program completion from API.'));
        }
        $this->boosterCompletion = $boosterCompletion;

        // Identifiants Freshdesk des négociateurs
        $this->freshdeskUserIds = $this->mandataryHelper->getFreshdeskUserIds();

        /**
         * L'évaluation Freshdesk la plus récente,
         * tous négociateurs confondus.
         *
         * @var \App\Entity\Event\FreshdeskFeedbackEvent|null
         */
        $freshdeskFeedbackEvent = $this->em->getRepository(\App\Entity\Event\FreshdeskFeedbackEvent::class)->findLatest();

        // Évaluations Freshdesk laissées depuis la plus récente qui soit déjà importée, tous négociateurs confondus
        $sinceDate = null !== $freshdeskFeedbackEvent ? $freshdeskFeedbackEvent->getDate()->modify('+1 second') : null;
        $this->freshdeskFeedbacks = $this->mandataryHelper->getFreshdeskFeedbacks(null, $sinceDate);
    }

    /**
     * Récupère un coach à partir de son adresse e-mail.
     *
     * @param string $email
     *
     * @return Coach|null
     */
    private function getCoach($email): ?Coach
    {
        return $this
            ->em
            ->getRepository(Coach::class)
            ->findOneByEmail($email)
        ;
    }

    /**
     * Récupère un négociateur à partir de son adresse e-mail.
     *
     * @param string $email
     *
     * @return Mandatary|null
     */
    private function getMandatary($email): ?Mandatary
    {
        return $this
            ->em
            ->getRepository(Mandatary::class)
            ->findOneByEmail($email)
        ;
    }
}
