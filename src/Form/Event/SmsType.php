<?php

namespace App\Form\Event;

use App\Entity\Event\SmsEvent;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les SMS.
 */
class SmsType extends AbstractEventType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];

        $builder
            ->add('date', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Date et heure d'envoi du SMS"),
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('content', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Texte du SMS"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-sms-content-%s', $mandatary->getId()),
                ],
                'help' => $this->getTranslator()->trans("Aucune signature automatique ne sera ajoutée au SMS."),
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
        ;

        // Enfin, appelle la méthode d'AbstractEventType qui mutualise les traitements par défaut
        parent::buildForm($builder, $options);
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmsEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
