<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload de fichiers.
 */
class FileUploader
{
    /**
     * @var string
     */
    private $assetsDirectory;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->assetsDirectory = sprintf('%s/public/assets', $parameterBag->get('kernel.project_dir'));
    }

    /**
     * Déplace le fichier local $file dans le dossier $folder.
     *
     * Le répertoire $folder sera créé s'il n'existe pas déjà.
     *
     * @param UploadedFile $file
     * @param string       $folder
     *
     * @return bool|string
     */
    public function upload(UploadedFile $file, $folder)
    {
        $ext = $file->guessExtension();
        $path = sprintf('%s/%s', $this->assetsDirectory, $folder);
        $filename = sprintf('%s.%s', md5(uniqid()), $ext);

        if (!file_exists($path)) {
            mkdir($path, 0777, true); // recursive = true <=> mkdir -p
        }

        try {
            $file->move($path, $filename);
        } catch (FileException $e) {
            $filename = false;
        }

        return $filename;
    }

    /**
     * Déplace le fichier distant $url dans le dossier $folder.
     *
     * Le répertoire $folder sera créé s'il n'existe pas déjà.
     *
     * @param string $url
     * @param string $folder
     *
     * @return bool|string
     */
    public function uploadByUrl($url, $folder)
    {
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        $path = sprintf('%s/%s', $this->assetsDirectory, $folder);
        $filename = sprintf('%s.%s', md5(uniqid()), $ext);

        if (!file_exists($path)) {
            mkdir($path, 0777, true); // recursive = true <=> mkdir -p
        }

        try {
            if (!copy($url, sprintf('%s/%s', $path, $filename))) {
                $filename = false;
            }
        } catch (\Exception $e) {
            $filename = false;
        }

        return $filename;
    }

    /**
     * Supprime un fichier dans le dossier $folder.
     *
     * @param string $filename
     * @param string $folder
     *
     * @return void
     */
    public function delete($filename, $folder): void
    {
        $filepath = sprintf('%s/%s/%s', $this->assetsDirectory, $folder, $filename);

        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Teste l'existence d'un fichier dans le dossier $folder.
     *
     * @param string $filename
     * @param string $folder
     *
     * @return bool
     */
    public function exists($filename, $folder): bool
    {
        $filepath = sprintf('%s/%s/%s', $this->assetsDirectory, $folder, $filename);

        return file_exists($filepath);
    }

    /**
     * Texte l'existence d'une image.
     *
     * @param string $url
     *
     * @return bool Renvoie true si l'URL renvoie une image, false sinon
     */
    public function imageExists($url): bool
    {
        return false !== getimagesize($url);
    }

    /**
     * Teste l'égalité de deux fichiers pour savoir si ce sont les mêmes.
     *
     * @param string $filepath1 Chemin ou URL d'un fichier
     * @param string $filepath2 Chemin ou URL d'un fichier
     *
     * @return bool
     */
    public function equals($filepath1, $filepath2): bool
    {
        return sha1_file($filepath1) === sha1_file($filepath2);
    }

    /**
     * Récupère le chemin d'un fichier.
     *
     * @param string $filename
     * @param string $folder
     *
     * @return string
     */
    public function getFilepath($filename, $folder): string
    {
        return sprintf('%s/%s/%s', $this->assetsDirectory, $folder, $filename);
    }
}
