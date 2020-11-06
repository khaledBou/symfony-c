<?php

namespace App\Form\Event;

use App\Entity\Event\MandataryReminderEvent;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les relances.
 */
class MandataryReminderType extends AbstractEventType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];
        $coach = $options['coach'];
        $tradesCount = $mandatary->getTradesCount();
        $compromisesCount = $mandatary->getCompromisesCount();
        $salesCount = $mandatary->getSalesCount();

        // Les modèles disponibles (les textes doivent rester génériques pour tous les réseaux)
        $choices = [
            $this->getTranslator()->trans("aucun") => 'none',
            $this->getTranslator()->trans("point") => $this->getTranslator()->trans(
                "Bonjour %mandatary%, je souhaite faire un point avec vous, quelles sont vos disponibilités cette semaine ? Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%phone%' => $coach->getPhone(),
                ]
            ),
        ];

        if ($tradesCount > 0) {
            /**
             * Il n'est pas possible d'utiliser la syntaxe ICU sans passer par des fichiers de traduction.
             *
             * @see https://symfony.com/doc/current/translation.html#message-format
             */
            $choices[$this->getTranslator()->trans("mandats")] = $tradesCount > 1 ? $this->getTranslator()->trans(
                "Bonjour %mandatary%, votre fichier compte %trades_count% mandats, une nouvelle étape commence. Je vous propose un point pour préparer les appels, les visites, l'organisation… Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%trades_count%' => $tradesCount,
                    '%phone%' => $coach->getPhone(),
                ]
            ) : $this->getTranslator()->trans(
                "Bonjour %mandatary%, 1er mandat, une nouvelle étape commence. Je vous propose un point pour préparer les appels, les visites, l'organisation… Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%phone%' => $coach->getPhone(),
                ]
            );
        }

        if ($compromisesCount > 0) {
            /**
             * Il n'est pas possible d'utiliser la syntaxe ICU sans passer par des fichiers de traduction.
             *
             * @see https://symfony.com/doc/current/translation.html#message-format
             */
            $choices[$this->getTranslator()->trans("compromis")] = $compromisesCount > 1 ? $this->getTranslator()->trans(
                "Bonjour %mandatary%, vous avez signé %compromises_count% compromis, félicitations. Un point avec votre coach s'impose pour bien préparer la suite. Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%compromises_count%' => $compromisesCount,
                    '%phone%' => $coach->getPhone(),
                ]
            ) : $this->getTranslator()->trans(
                "Bonjour %mandatary%, vous avez signé votre premier compromis, félicitations. Un point avec votre coach s'impose pour bien préparer la suite. Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%phone%' => $coach->getPhone(),
                ]
            );
        }

        if ($salesCount > 0) {
            $salesCount = floor($salesCount);

            /**
             * Il n'est pas possible d'utiliser la syntaxe ICU sans passer par des fichiers de traduction.
             *
             * @see https://symfony.com/doc/current/translation.html#message-format
             */
            $choices[$this->getTranslator()->trans("ventes")] = $salesCount > 1 ? $this->getTranslator()->trans(
                "Bonjour %mandatary%, %sales_count% ventes, quelle réussite, bravo. On prépare la prochaine ? Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%sales_count%' => $salesCount,
                    '%phone%' => $coach->getPhone(),
                ]
            ) : $this->getTranslator()->trans(
                "Bonjour %mandatary%, 1ère vente, quelle réussite, bravo. On prépare la prochaine ? Rappelez-moi au %phone%. A bientot.",
                [
                    '%mandatary%' => $mandatary->getFirstName(),
                    '%phone%' => $coach->getPhone(),
                ]
            );
        }

        $builder
            ->add('date', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Date et heure d'envoi de la relance"),
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('way', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Relance par"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => [
                    $this->getTranslator()->trans("e-mail") => MandataryReminderEvent::WAY_EMAIL,
                    $this->getTranslator()->trans("SMS") => MandataryReminderEvent::WAY_SMS,
                ],
                'data' => MandataryReminderEvent::WAY_EMAIL, // e-mail par défaut
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('model', Type\ChoiceType::class, [
                'mapped' => false,
                'label' => $this->getTranslator()->trans("Modèle de relance"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => $choices,
                'data' => 'none', // valeur par défaut
                'help' => $this->getTranslator()->trans("Permet de prédéfinir le texte de la relance."),
            ])
            ->add('content', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Texte de la relance"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-mandatary-reminder-content-%s', $mandatary->getId()),
                ],
                'help' => $this->getTranslator()->trans("Une signature automatique sera ajoutée pour les relances par e-mail uniquement."),
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
        ;

        // Définit la date de relance par défaut à J+30
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            // @var MandataryReminderEvent
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
            'data_class' => MandataryReminderEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
