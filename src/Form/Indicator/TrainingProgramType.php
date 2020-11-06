<?php

namespace App\Form\Indicator;

use App\Entity\Indicator\TrainingProgramIndicator;
use App\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour les indicateurs de réalisation du programme de formation (Booster, Starter de l'Immo, …).
 */
class TrainingProgramType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @var bool
        $isDisabled = $options['is_disabled'];

        $builder
            ->add('completedMissions', Type\TextareaType::class, [
                'label' => null !== $options['indicator_name'] ? $options['indicator_name'] : $this->getTranslator()->trans("Missions de formation réalisées"),
                'help' => $this->getTranslator()->trans("Une mission par ligne."),
                'help_attr' => [
                    'class' => $isDisabled ? 'sr-only' : null,
                ],
                'attr' => [
                    'disabled' => $isDisabled,
                    'class' => 'textarea-autosize',
                ],
            ])
        ;

        if (!$isDisabled) {
            $builder
                ->add('submit', Type\SubmitType::class, [
                    'label' => $this->getTranslator()->trans("Mettre à jour l'indicateur"),
                    'attr' => [
                        'class' => 'hidden',
                    ],
                ])
            ;
        }

        /* Les missions (array) étant représentées dans le formulaire par une string,
         * il est nécessaire d'indiquer à Symfony comment convertir
         * l'array en string (pour l'affichage) et la string en array (pour l'enregistrement). */
        $builder
            ->get('completedMissions')
            ->addModelTransformer(new CallbackTransformer(
                function (?array $missions) {
                    return null === $missions ? '' : implode("\n", $missions); // array to string
                },
                function (string $missions) {
                    return explode("\n", $missions); // string to array
                }
            ))
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TrainingProgramIndicator::class,
            'indicator_name' => null, // Nom de l'indicateur
            'is_disabled' => false, // Indique si le formulaire est éditable ou non
        ]);
    }
}
