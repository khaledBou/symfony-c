<?php

namespace App\Form\Event;

use App\Entity\Event\CallEvent;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les appels téléphoniques.
 */
class CallType extends AbstractEventType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];

        $builder
            ->add('date', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Date et heure de l'appel"),
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('incoming', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Appel"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => [
                    $this->getTranslator()->trans("sortant") => false,
                    $this->getTranslator()->trans("entrant") => true,
                ],
                'data' => false, // sortant par défaut
                'constraints' => [
                    new Constraints\NotNull(),
                ],
            ])
            ->add('report', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Compte rendu"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-call-report-%s', $mandatary->getId()),
                ],
                'required' => false,
            ])
        ;

        /* Lorsque le formulaire est envoyé, s'il s'agit d'un appel entrant,
         * on supprime le coach qui avait été associé par défaut dans le contrôleur. */
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent) {
            // @var array
            $event = $formEvent->getData();

            if (!empty($event['incoming'])) {
                unset($event['coach']);
            }

            $formEvent->setData($event);
        });

        // Enfin, appelle la méthode d'AbstractEventType qui mutualise les traitements par défaut
        parent::buildForm($builder, $options);
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CallEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
