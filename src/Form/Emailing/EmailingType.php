<?php

namespace App\Form\Emailing;

use App\Entity\User\Mandatary;
use App\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les e-mailings.
 */
class EmailingType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach ($options['mandataries'] as $mandatary) {
            $label = sprintf('%s (%s)', $mandatary, $mandatary->getEmail());
            $value = $mandatary->getEmail();
            $choices[$label] = $value;
        }

        $builder
            ->add('mandataries', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Négociateurs"),
                'choices' => $choices,
                'multiple' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'groups' => [
                            'emailing',
                        ],
                    ]),
                ],
            ])
            ->add('subject', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Objet"),
                'constraints' => [
                    new Constraints\NotBlank([
                        'groups' => [
                            'emailing',
                        ],
                    ]),
                ],
            ])
            ->add('content', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Corps du mail"),
                'attr' => [
                    'class' => 'textarea-autosize',
                ],
                'constraints' => [
                    new Constraints\NotBlank([
                        'groups' => [
                            'emailing',
                        ],
                    ]),
                ],
            ])
            ->add('test', Type\SubmitType::class, [
                'label' => $this->getTranslator()->trans("Tester"),
            ])
            ->add('send', Type\SubmitType::class, [
                'label' => $this->getTranslator()->trans("Envoyer"),
            ])
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // @var Mandatary[]
            'mandataries' => [],
            'validation_groups' => ['emailing'], // ne pas mettre 'Default' désactive la validation des négociateurs en cascade
        ]);
    }
}
