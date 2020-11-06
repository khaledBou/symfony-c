<?php

namespace App\Form\Event;

use App\Entity\Event\CommentEvent;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Formulaire pour les commentaires.
 */
class CommentType extends AbstractEventType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mandatary = $options['mandatary'];

        $builder
            ->add('comment', Type\TextareaType::class, [
                'label' => $this->getTranslator()->trans("Commentaire"),
                'attr' => [
                    'class' => 'textarea-autosize',
                    'data-local-storage-id' => sprintf('mandatary-comment-comment-%s', $mandatary->getId()),
                ],
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
            'data_class' => CommentEvent::class,
            'coach' => null,
            'mandatary' => null,
        ]);
    }
}
