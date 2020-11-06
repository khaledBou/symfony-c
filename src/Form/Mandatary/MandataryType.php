<?php

namespace App\Form\Mandatary;

use App\Entity\User\Mandatary;
use App\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour les négociateurs.
 */
class MandataryType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('careLevel', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Besoin d'accompagnement"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'choices' => [
                    $this->getTranslator()->trans("intensif") => Mandatary::CARE_LEVEL_HIGH,
                    $this->getTranslator()->trans("modéré") => Mandatary::CARE_LEVEL_MEDIUM,
                    $this->getTranslator()->trans("léger") => Mandatary::CARE_LEVEL_LOW,
                ],
                'expanded' => true, // radios
            ])
            ->add('potential', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Potentiel commercial"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'choices' => [
                    $this->getTranslator()->trans("très faible") => Mandatary::POTENTIAL_VERY_LOW,
                    $this->getTranslator()->trans("faible") => Mandatary::POTENTIAL_LOW,
                    $this->getTranslator()->trans("moyen") => Mandatary::POTENTIAL_MEDIUM,
                    $this->getTranslator()->trans("haut") => Mandatary::POTENTIAL_HIGH,
                    $this->getTranslator()->trans("très haut") => Mandatary::POTENTIAL_VERY_HIGH,
                ],
                'expanded' => true, // radios
            ])
            ->add('skilled', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Confirmé"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
            ])
            ->add('couldBeDeveloper', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Tempérament de développeur"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
            ])
            ->add('couldBeAnimator', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Tempérament d'animateur"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
            ])
            ->add('couldBeTrainer', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Tempérament de formateur"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
            ])
            ->add('submit', Type\SubmitType::class, [
                'label' => $this->getTranslator()->trans("Mettre à jour"),
                'attr' => [
                    'class' => 'hidden',
                ],
            ])
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mandatary::class,
        ]);
    }
}
