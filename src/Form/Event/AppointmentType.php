<?php

namespace App\Form\Event;

use App\Entity\Event\AppointmentEvent;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les rendez-vous.
 */
class AppointmentType extends AbstractEventType
{
    // Les durées possibles des rendez-vous, en minutes
    const DURATION_10 = 10,
          DURATION_20 = 20,
          DURATION_30 = 30;

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];

        $builder
            ->add('date', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Date et heure de début"),
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('model', Type\ChoiceType::class, [
                'mapped' => false,
                'label' => $this->getTranslator()->trans("Modèle de rendez-vous"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choices' => [ // les modèles disponibles (les textes doivent rester génériques pour tous les réseaux)
                    $this->getTranslator()->trans("aucun") => 'none',
                    $this->getTranslator()->trans("1er RDV avec coach") => sprintf(
                        '%s|%s|%s',
                        self::DURATION_20, // durée, en minutes
                        $this->getTranslator()->trans("Outils, formations, démarrage activité…"), // objet
                        $this->getTranslator()->trans("C'est un RDV d'accueil dans le réseau et le début de votre accompagnement. Nous allons revenir sur les outils et les formations. Le RDV dure environ 20 minutes. À bientôt.") // description
                    ),
                    $this->getTranslator()->trans("RDV activité") => sprintf(
                        '%s|%s|%s',
                        self::DURATION_30,
                        $this->getTranslator()->trans("Prise de mandats, bilan formations, outils…"),
                        $this->getTranslator()->trans("Je souhaite prendre de vos nouvelles, faire un point sur votre activité, vos formations, vos mandats. À bientôt.")
                    ),
                    $this->getTranslator()->trans("RDV accompagnement") => sprintf(
                        '%s|%s|%s',
                        self::DURATION_30,
                        $this->getTranslator()->trans("Point activité"),
                        $this->getTranslator()->trans("Analysons ensemble votre activité, le nombre de mandats, les actions commerciales, la prospection terrain et la relance clients. À bientôt.")
                    ),
                ],
                'data' => 'none', // valeur par défaut
                'help' => $this->getTranslator()->trans("Permet de prédéfinir la durée, l'objet et la description du rendez-vous."),
            ])
            ->add('duration', Type\ChoiceType::class, [
                'label' => $this->getTranslator()->trans("Durée"),
                'label_attr' => [
                    'class' => 'radio__label',
                ],
                'expanded' => true, // radios
                'choice_loader' => new CallbackChoiceLoader(function () {
                    foreach ([
                        self::DURATION_10,
                        self::DURATION_20,
                        self::DURATION_30,
                    ] as $min) {
                        yield $this->getTranslator()->trans("%minutes% min", ['%minutes%' => $min]) => $min;
                    }
                }),
                'data' => 10, // 10 min par défaut
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('subject', Type\TextType::class, [
                'label' => $this->getTranslator()->trans("Objet"),
                'attr' => [
                    'data-local-storage-id' => sprintf('mandatary-appointment-subject-%s', $mandatary->getId()),
                ],
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ])
            ->add('description', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Description"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-appointment-description-%s', $mandatary->getId()),
                ],
                'required' => false,
                'help' => $this->getTranslator()->trans("Aucune signature automatique ne sera ajoutée à la description du rendez-vous."),
            ])
        ;

        // Définit la date par défaut
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            // @var AppointmentEvent
            $event = $formEvent->getData();
            $event->setDate(new \DateTime('+1 weekday 09:00:00')); // prochain jour de semaine à 9h00
            $formEvent->setData($event);
        });

        /* L'intervalle de temps (\DateInterval) étant représenté dans le formulaire par un nombre entier de minutes,
         * il est nécessaire d'indiquer à Symfony comment convertir
         * le nombre entier en string (pour l'affichage) puis en \DateInterval (pour l'enregistrement). */
        $builder->get('duration')
            ->addModelTransformer(new CallbackTransformer(
                function (?int $minutes) {
                    return null === $minutes ? '' : (string) $minutes; // int to string
                },
                function (int $minutes) {
                    return new \DateInterval(sprintf('PT%dM', $minutes)); // int to \DateInterval
                }
            ))
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
            'data_class' => AppointmentEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
