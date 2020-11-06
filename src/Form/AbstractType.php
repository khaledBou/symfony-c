<?php

namespace App\Form;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base de formulaire commune à tous les formulaires nécessitant le traducteur.
 */
class AbstractType extends \Symfony\Component\Form\AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}
