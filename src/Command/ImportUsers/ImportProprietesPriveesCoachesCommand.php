<?php

namespace App\Command\ImportUsers;

use App\Entity\User\Coach;
use App\Entity\User\ImportableUserInterface;
use App\Service\FileUploader;
use App\Service\MandataryHelper;
use App\Service\ProprietesPriveesApiHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Commande d'import des coachs Proprietes-Privees.
 *
 * Importe les coachs depuis l'API du CRM Proprietes-Privees.
 * Cette commande gère la mise à jour des coachs et peut donc être jouée plusieurs fois.
 */
class ImportProprietesPriveesCoachesCommand extends AbstractImportUsersCommand
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
    const NETWORK = 'pp';

    /**
     * @var string
     */
    protected static $defaultName = 'app:user:import:coaches:pp';

    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var FileUploader
     */
    private $fileUploader;

    /**
     * @var string
     */
    private $avatarUrl;

    /**
     * @param EntityManagerInterface       $em
     * @param ParameterBagInterface        $parameterBag
     * @param MandataryHelper              $mandataryHelper
     * @param ProprietesPriveesApiHelper   $proprietesPriveesApiHelper
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param FileUploader                 $fileUploader
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $parameterBag, MandataryHelper $mandataryHelper, ProprietesPriveesApiHelper $proprietesPriveesApiHelper, UserPasswordEncoderInterface $passwordEncoder, FileUploader $fileUploader)
    {
        parent::__construct($em, $parameterBag, $mandataryHelper);

        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;
        $this->passwordEncoder = $passwordEncoder;
        $this->fileUploader = $fileUploader;
        $this->avatarUrl = $parameterBag->get('proprietes_privees_coach_avatar_url');
    }

    /**
     * @inheritdoc
     */
    public function getUserFromRepository($userData): ?ImportableUserInterface
    {
        return $this
            ->em
            ->getRepository(self::USER_CLASS)
            ->findOneBy(['email' => $userData->employee->professionalEmailAddress])
        ;
    }

    /**
     * @inheritdoc
     */
    public function getUsersData(): array
    {
        $output = $this->proprietesPriveesApiHelper->call('employee-account', []);

        $usersData = [];

        if (null !== $output && $output->success) {
            foreach ($output->data as $userData) {
                // Ne considère que les coachs actifs ayant une adresse email
                if ($userData->account->active && $userData->employee->isCoach && $userData->employee->professionalEmailAddress) {
                    $usersData[] = $userData;
                }
            }
        } else {
            throw new \Exception(
                null === $output ?
                'Could not get coaches from API.' :
                sprintf('Could not get coaches from API: %s (%s)', $output->reason, $output->detail)
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
            ->setEmail($userData->employee->professionalEmailAddress)
            ->setNetwork(self::NETWORK)
            ->setImported(true)
            ->setEnabled(false === $user->isEnabled() ? false : true)
            ->setFirstName($userData->employee->firstName)
            ->setLastName($userData->employee->lastName)
            ->setPhone($userData->employee->professionalPhoneNumber)
        ;

        // Définit un mot de passe une fois pour toutes
        $hasPassword = null !== $user->getPassword();
        if (!$hasPassword) {
            $user->setPassword($this->passwordEncoder->encodePassword($user, uniqid()));
        }

        // Avatar
        $file = sprintf('%s.png', $userData->account->code);
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
            ->setDescription('Import des coachs Proprietes-Privees')
            ->setHelp('Importe les coachs depuis l\'API du CRM Proprietes-Privees')
        ;
    }
}
