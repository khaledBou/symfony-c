<?php

namespace App\Form\Event;

use App\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Base de formulaire commune à tous les formulaires d'événements.
 */
class AbstractEventType extends AbstractType
{
    /**
     * @var string
     */
    const DATE_FORMAT = 'd/m/Y H:i';

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* Les dates (\DateTime) étant représentées dans le formulaire par des strings,
         * il est nécessaire d'indiquer à Symfony comment convertir
         * les \DateTime en strings (pour l'affichage) et les strings en \DateTime (pour l'enregistrement). */
        if ($builder->has('date')) {
            $builder->get('date')
                ->addModelTransformer(new CallbackTransformer(
                    function (?\DateTime $date) {
                        return null === $date ? '' : $date->format(self::DATE_FORMAT); // \DateTime to string
                    },
                    function (string $string) {
                        return \DateTime::createFromFormat(self::DATE_FORMAT, $string); // string to \DateTime
                    }
                ))
            ;
        }

        $builder
            ->add('coach', null, [
                'label_attr' => [
                    'class' => 'hidden',
                ],
                'attr' => [
                    'class' => 'hidden',
                ],
            ])
            ->add('submit', Type\SubmitType::class, [
                'label' => $this->getTranslator()->trans("Créer l'événement"),
            ])
        ;
    }
}
