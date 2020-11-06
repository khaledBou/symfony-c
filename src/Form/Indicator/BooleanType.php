<?php

namespace App\Form\Indicator;

use App\Entity\Indicator\BooleanIndicator;
use App\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour les indicateurs booléens.
 */
class BooleanType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @var bool
        $isDisabled = $options['is_disabled'];

        $builder
            ->add('value', Type\CheckboxType::class, [
                'label' => null !== $options['indicator_name'] ? $options['indicator_name'] : $this->getTranslator()->trans("Valeur"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
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
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BooleanIndicator::class,
            'indicator_name' => null, // Nom de l'indicateur
            'is_disabled' => false, // Indique si le formulaire est éditable ou non
        ]);
    }
}
