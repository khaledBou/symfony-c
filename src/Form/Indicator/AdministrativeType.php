<?php

namespace App\Form\Indicator;

use App\Entity\Indicator\AdministrativeIndicator;
use App\Entity\User\Mandatary;
use App\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire pour les indicateurs de situation administrative.
 */
class AdministrativeType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // @var bool
        $isDisabled = $options['is_disabled'];

        // @var Mandatary
        $mandatary = $options['data']->getMandatary();

        // @var string|null
        $contract = $mandatary->getContract();

        /**
         * Les pièces administratives à fournir,
         * selon le type de contrat du négociateur.
         *
         * @var string[]
         */
        $todos = isset(Mandatary::ADMINISTRATIVE_DOCUMENTS[$contract]) ?
                       Mandatary::ADMINISTRATIVE_DOCUMENTS[$contract] : [];

        if (in_array('rsac', $todos)) {
            $builder->add('validRsac', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("RSAC valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

        if (in_array('siret', $todos)) {
            $builder->add('validSiret', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("SIRET valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

        if (in_array('rcp', $todos)) {
            $builder->add('validRcp', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("RCP valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

        if (in_array('cci', $todos)) {
            $builder->add('validCci', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Habilitation CCI valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

        if (in_array('tva', $todos)) {
            $builder->add('validTva', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Numéro de TVA valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

        if (in_array('portage', $todos)) {
            $builder->add('validPortage', Type\CheckboxType::class, [
                'label' => $this->getTranslator()->trans("Convention d'adhésion ou contrat de portage valide"),
                'label_attr' => [
                    'class' => 'checkbox__label',
                ],
                'required' => false,
                'attr' => [
                    'disabled' => $isDisabled,
                ],
            ]);
        }

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
            'data_class' => AdministrativeIndicator::class,
            'indicator_name' => null, // Nom de l'indicateur
            'is_disabled' => false, // Indique si le formulaire est éditable ou non
        ]);
    }
}
