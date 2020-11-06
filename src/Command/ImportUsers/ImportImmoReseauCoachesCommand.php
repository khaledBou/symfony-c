<?php

namespace App\Command\ImportUsers;

use App\Entity\User\Coach;
use App\Entity\User\ImportableUserInterface;
use App\Service\FileUploader;
use App\Service\ImmoReseauApiHelper;
use App\Service\MandataryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Commande d'import des coachs Immo-Reseau.
 *
 * Importe les coachs depuis l'API du CRM Immo-Reseau.
 * Cette commande gère la mise à jour des coachs et peut donc être jouée plusieurs fois.
 */
class ImportImmoReseauCoachesCommand extends AbstractImportUsersCommand
{
    /**
     * Le fully-qualified name de la classe utilisateur.
     *
     * @var string
     */
    const USER_CLASS = Coach::class;

    /**
     * L'identifiant du réseau pour lequel importer les coachs.
     *
     * @var string
     */
    const NETWORK = 'ir';

    /**
     * @var string
     */
    protected static $defaultName = 'app:user:import:coaches:ir';

    /**
     * @var ImmoReseauApiHelper
     */
    private $immoReseauApiHelper;

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var string
     */
    private $avatarUrl;

    /**
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface  $parameterBag
     * @param MandataryHelper        $mandataryHelper
     * @param ImmoReseauApiHelper    $immoReseauApiHelper
     * @param FileUploader           $fileUploader
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, MandataryHelper $mandataryHelper, ImmoReseauApiHelper $immoReseauApiHelper, FileUploader $fileUploader)
    {
        parent::__construct($em, $parameterBag, $mandataryHelper);

        $this->immoReseauApiHelper = $immoReseauApiHelper;
        $this->fileUploader = $fileUploader;
        $this->avatarUrl = $parameterBag->get('immo_reseau_avatar_url');
    }

    /**
     * @inheritdoc
     */
    public function getUserFromRepository($userData): ?ImportableUserInterface
    {
        return $this
            ->em
            ->getRepository(self::USER_CLASS)
            ->findOneBy(['email' => trim($userData->email)])
        ;
    }

    /**
     * @inheritdoc
     */
    public function getUsersData(): array
    {
        $output = $this->immoReseauApiHelper->call('utilisateurs', [
            'enabled' => 'true',
            'pagination' => 'false',
            'grade' => '6', // correspond aux administrateurs
        ]);

        $usersData = [];

        if (isset($output->{'hydra:member'})) {
            foreach ($output->{'hydra:member'} as $userData) {
                // Ne considère que les coachs ayant une adresse email
                if ($userData->email) {
                    $usersData[] = $userData;
                }
            }
        } else {
            throw new \Exception(
                null === $output ?
                'Could not get coaches from API.' :
                sprintf('Could not get coaches from API: %s (%s)', $output->title, $output->detail)
            );
        }

        return $usersData;
    }

    /**
     * @inheritdoc
     */
    public function setUserAttributes(ImportableUserInterface $user, $userData): ImportableUserInterface
    {
        $user
            ->setEmail(trim($userData->email))
            ->setNetwork(self::NETWORK)
            ->setImported(true)
            ->setEnabled(false === $user->isEnabled() ? false : true)
            ->setPassword($userData->password)
            ->setFirstName($userData->prenom ? $userData->prenom : "N.C.")
            ->setLastName($userData->nom ? $userData->nom : "N.C.")
            ->setPhone(str_replace(' ', '', $userData->telephonePro))
        ;

        // Avatar
        $file = $userData->lastUrlImageProfilValid;
        if (!empty($file)) {
            $upload = true;

            $url = sprintf('%s/%s', $this->avatarUrl, $file); // l'URL de l'avatar
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

        return $user;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import des coachs Immo-Reseau')
            ->setHelp('Importe les coachs depuis l\'API du CRM Immo-Reseau')
        ;
    }
}
