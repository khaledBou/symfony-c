<?php

namespace App\Form\Event;

use App\Entity\Event\CoachReminderEvent;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les rappels.
 */
class CoachReminderType extends AbstractEventType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];

        $builder
            ->add('date', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Date et heure d'envoi du rappel"),
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('way', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Rappel par"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => [
                    $this->getTranslator()->trans("notification") => CoachReminderEvent::WAY_NOTIFICATION,
                    $this->getTranslator()->trans("e-mail") => CoachReminderEvent::WAY_EMAIL,
                ],
                'data' => CoachReminderEvent::WAY_NOTIFICATION, // notification par défaut
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('model', Type\ChoiceType::class, [
                'mapped' => false,
                'label' => $this->getTranslator()->trans("Modèle de rappel"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => [ // les modèles disponibles (les textes doivent rester génériques pour tous les réseaux)
                    $this->getTranslator()->trans("aucun") => 'none',
                    $this->getTranslator()->trans("démarches administratives faites") => $this->getTranslator()->trans("démarches administratives faites"),
                    $this->getTranslator()->trans("formations") => $this->getTranslator()->trans("formations"),
                    $this->getTranslator()->trans("prospection") => $this->getTranslator()->trans("prospection"),
                    $this->getTranslator()->trans("changement pack") => $this->getTranslator()->trans("changement pack"),
                    $this->getTranslator()->trans("statut") => $this->getTranslator()->trans("statut"),
                    $this->getTranslator()->trans("parrainage/animation") => $this->getTranslator()->trans("parrainage/animation"),
                    $this->getTranslator()->trans("autonome publication") => $this->getTranslator()->trans("autonome publication"),
                    $this->getTranslator()->trans("projet communication") => $this->getTranslator()->trans("projet communication"),
                    $this->getTranslator()->trans("étude de marché faite") => $this->getTranslator()->trans("étude de marché faite"),
                    $this->getTranslator()->trans("1er RDV mandat") => $this->getTranslator()->trans("1er RDV mandat"),
                    $this->getTranslator()->trans("appel collègues") => $this->getTranslator()->trans("appel collègues"),
                    $this->getTranslator()->trans("résiliation") => $this->getTranslator()->trans("résiliation"),
                    $this->getTranslator()->trans("déménagement") => $this->getTranslator()->trans("déménagement"),
                    $this->getTranslator()->trans("cooptation") => $this->getTranslator()->trans("cooptation"),
                ],
                'data' => 'none', // valeur par défaut
                'help' => $this->getTranslator()->trans("Permet de prédéfinir le texte du rappel."),
            ])
            ->add('content', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Texte du rappel"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-coach-reminder-content-%s', $mandatary->getId()),
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
        ;

        // Définit la date de rappel par défaut à J+30
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            // @var CoachReminderEvent
            $event = $formEvent->getData();
            $event->setDate(new \DateTime('+30 days 09:00:00'));
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
            'data_class' => CoachReminderEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
