<?php

namespace App\Command\ImportUsers;

use App\Entity\User\ImportableUserInterface;
use App\Entity\User\Mandatary;
use App\Service\GoogleApiHelper;
use App\Service\MandataryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'import des négociateurs Rezoximo.
 *
 * Importe les négociateurs depuis le Google Sheets des utilisateurs Rezoximo.
 * Cette commande gère la mise à jour des négociateurs et peut donc être jouée plusieurs fois.
 */
class ImportRezoximoMandatariesCommand extends AbstractImportUsersCommand
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
    const NETWORK = 'rz';

    /**
     * Correspondance entre les contrats du Google Sheets et ceux de l'application.
     *
     * @var array
     */
    const CONTRACTS = [
        'agent commercial (auto entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto-entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro-entrepreneur)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (auto-entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (micro-entreprise)' => Mandatary::CONTRACT_MICRO_ENTREPRENEUR,
        'agent commercial (normal)' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'agent commercial' => Mandatary::CONTRACT_AGENT_COMMERCIAL,
        'portage salarial' => Mandatary::CONTRACT_PORTAGE_SALARIAL,
    ];

    /**
     * @var string
     */
    protected static $defaultName = 'app:user:import:mandataries:rz';

    /**
     * @var GoogleApiHelper
     */
    private $googleApiHelper;

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     * @param MandataryHelper        $mandataryHelper
     * @param GoogleApiHelper        $googleApiHelper
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, MandataryHelper $mandataryHelper, GoogleApiHelper $googleApiHelper)
    {
        parent::__construct($em, $parameterBag, $mandataryHelper);

        $googleApiHelper
            ->setClientScopes([
                \Google_Service_Sheets::SPREADSHEETS_READONLY,
            ])
            ->setClientAuthConfig('config/google/credentials/client_secrets.import-utilisateurs-rezoximo.json')
        ;
        $this->googleApiHelper = $googleApiHelper;
    }

    /**
     * @inheritdoc
     */
    public function getUserFromRepository($userData): ?ImportableUserInterface
    {
        return $this
            ->em
            ->getRepository(self::USER_CLASS)
            ->findOneBy(['email' => trim($userData['email'])])
        ;
    }

    /**
     * @inheritdoc
     */
    public function getUsersData(): array
    {
        $client = $this->googleApiHelper->getClient();
        $service = new \Google_Service_Sheets($client);

        $usersData = [];

        try {
            $response = $service->spreadsheets_values->get('1u-Ln13V5keoXlVOp_iAZjm1pSEOgUJYWI-KemcL1iKU', 'A:BV'); // @codingStandardsIgnoreLine
            $rows = $response->getValues();

            // Suppression des colonnes d'en-tête
            unset($rows[0]);
            unset($rows[1]);

            foreach ($rows as $row) {
                // Lignes vides en fin de fichier
                if (empty($row)) {
                    break;
                }

                $email = isset($row[21]) ? trim($row[21]) : null;

                // Ignore les utilisateurs sortis du réseau et ceux qui n'ont pas d'adresse e-mail
                if ($email && empty($row[25])) { // S'ils ont une adresse email et pas de date de fin
                    $usersData[] = [
                        'civility' => strtoupper(trim($row[0])),
                        'lastname' => trim($row[1]),
                        'firstname' => trim($row[2]),
                        'phone' => str_replace(' ', '', trim($row[19])),
                        'email' => $email,
                        'birth_date' => $row[9],
                        'beginning_date' => $row[24],
                        'termination_date' => !empty($row[25]) ? $row[25] : null,
                        'statut' => strtolower(trim($row[27])),
                        'regime' => strtolower(trim($row[28])),
                        'bareme' => isset($row[50]) ? $row[50] : '',
                        'zip_code' => $row[17],
                        'city' => $row[18],
                        'pack' => $row[32],
                    ];
                }
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Could not get mandataries from API: %s (%s)', $e->getMessage(), $e->getCode()));
        }

        return $usersData;
    }

    /**
     * @inheritdoc
     */
    public function setUserAttributes(ImportableUserInterface $user, $userData): ImportableUserInterface
    {
        $contractString = !empty($userData['regime']) ? sprintf('%s (%s)', $userData['statut'], $userData['regime']) : $userData['statut'];

        $user
            ->setEmail($userData['email'])
            ->setNetwork(self::NETWORK)
            ->setImported(true)
            ->setEnabled(false === $user->isEnabled() ? false : true)
            ->setFirstName($userData['firstname'] ? $userData['firstname'] : 'nc')
            ->setLastName($userData['lastname'] ? $userData['lastname'] : 'nc')
            ->setSlug(strstr($userData['email'], '@', true))
            ->setCivility($userData['civility'])
            ->setPhone($userData['phone'])
            ->setBirthDate(\DateTime::createFromFormat('d/m/Y', $userData['birth_date']))
            ->setBeginDate(\DateTime::createFromFormat('d/m/Y', $userData['beginning_date']))
            ->setTerminationDate(null !== $userData['termination_date'] ? \DateTime::createFromFormat('d/m/Y', $userData['termination_date']) : null)
            ->setZipCode($userData['zip_code'])
            ->setCity($userData['city'])
            ->setBareme(null !== $userData['bareme'] ? $userData['bareme'] : '')
            ->setContract(isset(self::CONTRACTS[$contractString]) ? self::CONTRACTS[$contractString] : null)
            ->setPack($userData['pack'])
            ->setCrmUrl('https://docs.google.com/spreadsheets/d/1u-Ln13V5keoXlVOp_iAZjm1pSEOgUJYWI-KemcL1iKU/edit') // @todo
            ->setWebsiteUrl('http://www.rezoximo.com') // @todo
            ->setSalesRevenue(0) // @todo
            ->setSalesRevenueHistory([0]) // @todo
        ;

        return $user;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import des négociateurs Rezoximo')
            ->setHelp('Importe les négociateurs depuis le Google Sheets des utilisateurs Rezoximo')
        ;
    }
}
