<?php

namespace App\Controller;

use App\Entity\User\Coach;
use App\Entity\User\Mandatary;
use App\Entity\Event;
use App\Entity\Event\EmailingEvent;
use App\Entity\Indicator;
use App\Entity\User\ImportableUserInterface;
use App\Form\Emailing\EmailingType;
use App\Form\Mandatary\MandataryType;
use App\Service\EmailHelper;
use App\Service\EventHelper;
use App\Service\MandataryHelper;
use App\Service\NetworkHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour les négociateurs.
 */
class MandataryController extends AbstractController
{
    // Placeholders pour dynamiser les données de l'e-mailing
    const EMAILING_PLACEHOLDER_FIRST_NAME = '[prenom]',
          EMAILING_PLACEHOLDER_LAST_NAME = '[nom]';

    /**
     * @var MandataryHelper
     */
    private $mandataryHelper;

    /**
     * @var EventHelper
     */
    private $eventHelper;

    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @param NetworkHelper        $networkHelper
     * @param TranslatorInterface  $translator
     * @param MandataryHelper      $mandataryHelper
     * @param EventHelper          $eventHelper
     * @param EmailHelper          $emailHelper
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(NetworkHelper $networkHelper, TranslatorInterface $translator, MandataryHelper $mandataryHelper, EventHelper $eventHelper, EmailHelper $emailHelper, FormFactoryInterface $formFactory)
    {
        parent::__construct($networkHelper, $translator);

        $this->mandataryHelper = $mandataryHelper;
        $this->eventHelper = $eventHelper;
        $this->emailHelper = $emailHelper;
        $this->formFactory = $formFactory;
    }

    /**
     * Liste des négociateurs.
     *
     * @Route("/{whichOnes}", name="mandatary_index", defaults={"whichOnes"="mes-negociateurs"}, requirements={"whichOnes": "mes-negociateurs|tous-les-negociateurs"})
     *
     * @param string $whichOnes Indique quels négociateurs charger
     *
     * @return Response
     */
    public function index(string $whichOnes = 'mes-negociateurs'): Response
    {
        /**
         * L'activation du filtre sur le coach permet d'accéder aux négociateurs
         * qui ne sont pas ceux de l'utilisateur courant.
         *
         * @var bool
         */
        $isCoachFilterEnabled = 'tous-les-negociateurs' === $whichOnes;

        $mandataries = $this->getDoctrine()
            ->getRepository(Mandatary::class)
            ->findBy(array_merge(
                [
                    'network' => $this->getNetworkHelper()->getNetworkId(),
                    'enabled' => true,
                ],
                $isCoachFilterEnabled ? [] : [
                    'coach' => $this->getUser(),
                ]
            ))
        ;

        $animators = $this->getDoctrine()
            ->getRepository(Mandatary::class)
            ->findAnimators($this->getNetworkHelper()->getNetworkId())
        ;

        $coaches = $this->getDoctrine()
            ->getRepository(Coach::class)
            ->findCoaches($this->getNetworkHelper()->getNetworkId())
        ;

        return $this->render(sprintf('mandatary/index.%s.html.twig', $this->getNetworkHelper()->getNetworkId()), [
            'mandataries' => $mandataries,
            'animators' => $animators,
            'coaches' => $coaches,
            'is_coach_filter_enabled' => $isCoachFilterEnabled,
        ]);
    }

    /**
     * Affichage d'un négociateur.
     *
     * @Route("/negociateur/{slug}", name="mandatary_show")
     *
     * @param Request   $request
     * @param Mandatary $mandatary
     *
     * @return Response
     *
     * @throws NotFoundHttpException   Lorsque l'utilisateur requêté ne fait pas partie du réseau en cours de consultation
     * @throws BadRequestHttpException Lorsqu'un formulaire est soumis via ajax mais que les données ne passent pas les contraintes de validation
     */
    public function show(Request $request, Mandatary $mandatary): Response
    {
        $networkId = $this->getNetworkHelper()->getNetworkId();

        if ($mandatary->getNetwork() !== $networkId || !$mandatary->isEnabled()) {
            throw $this->createNotFoundException();
        }

        // Pour le traitement des formulaires
        $em = $this->getDoctrine()->getManager();

        // Formulaire d'édition du négociateur
        $mandataryForm = $this->formFactory->createNamed('mandatary_form', MandataryType::class, $mandatary);

        // Traitement du formulaire
        $mandataryForm->handleRequest($request);
        if ($mandataryForm->isSubmitted()) {
            if ($mandataryForm->isValid()) {
                $em->persist($mandataryForm->getData());
                $em->flush();
            }
            if (!$mandataryForm->isValid() && $request->isXmlHttpRequest()) {
                throw new BadRequestHttpException((string) $mandataryForm->getErrors(true));
            }
        }

        // Configuration des indicateurs
        $indicatorsConfig = $this->getParameter('app.indicators');

        /**
         * Indicateurs.
         *
         * @var Indicator\IndicatorInterface[]
         */
        $indicators = $this->getDoctrine()
            ->getRepository(Indicator\AbstractIndicator::class)
            ->findByMandatary($mandatary)
        ;

        /**
         * Indicateurs présentés en tant que formulaires.
         *
         * @var \Symfony\Component\Form\FormView[]
         */
        $indicatorsForms = [];

        foreach ($indicators as $indicator) {
            $indicatorKey = $indicator->getKey();

            // Configuration de l'indicateur
            if (!isset($indicatorsConfig[$indicatorKey])) {
                continue;
            }
            $indicatorConfig = $indicatorsConfig[$indicatorKey];

            // Réseau de l'indicateur
            if (!in_array($networkId, $indicatorConfig['networks'])) {
                continue;
            }

            // Création du formulaire
            $formType = $indicatorConfig['form_type'];
            $isManualIndicator = null === $indicatorConfig['fill_method'];
            $form = $this->formFactory->createNamed(sprintf('%s_indicator_form', $indicatorKey), $formType, $indicator, [
                'indicator_name' => $indicatorConfig['name'],
                'is_disabled' => !$isManualIndicator,
            ]);

            // Traitement du formulaire
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $em->persist($form->getData());
                    $em->flush();
                }
                if (!$form->isValid() && $request->isXmlHttpRequest()) {
                    throw new BadRequestHttpException((string) $form->getErrors(true));
                }
            }

            $indicatorsForms[$indicatorKey] = $form->createView();
        }

        // Configuration des événements
        $eventsConfig = $this->getParameter('app.events');
        $eventsFilters = $this->getParameter('app.events_filters');

        /**
         * Formulaires de création des événéments.
         *
         * @var \Symfony\Component\Form\FormView[]
         */
        $eventsForms = [];

        foreach ($eventsConfig as $eventType => $eventConfig) {
            // Réseau de l'événement
            if (!in_array($networkId, $eventConfig['networks'])) {
                continue;
            }

            $formType = $eventConfig['form_type'];

            // Présence d'un formulaire ?
            if (null === $formType) {
                continue;
            }

            // @var string
            $eventEntity = $eventConfig['entity'];
            // @var Event\EventInterface
            $event = (new $eventEntity())
                ->setMandatary($mandatary)
                ->setCoach($this->getUser())
                ->setDeletable(true) // ces événements créés manuellement peuvent être supprimés
            ;

            // Création du formulaire
            $form = $this->formFactory->createNamed(sprintf('%s_event_form', $eventType), $formType, $event, [
                'mandatary' => $mandatary,
                'coach' => $this->getUser(),
            ]);

            // Traitement du formulaire
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $event = $form->getData();

                    /* Hook pour les traitements qui vont au delà
                     * du simple enregistrement des données du formulaire. */
                    $this->eventHelper->createHook($event);

                    $em->persist($event);
                    $em->flush();

                    $this->addFlash('success', $this->getTranslator()->trans("Événement créé."));

                    return $this->redirectToRoute('mandatary_show', [
                        'slug' => $mandatary->getSlug(),
                    ]);
                }
                if (!$form->isValid() && $request->isXmlHttpRequest()) {
                    throw new BadRequestHttpException((string) $form->getErrors(true));
                }
            }

            $eventsForms[$eventType] = $form->createView();
        }

        /**
         * Événements.
         *
         * La récupération des événements se fait après traitement des formulaires de création.
         *
         * @var Event\EventInterface[]
         */
        $events = $this->getDoctrine()
            ->getRepository(Event\AbstractEvent::class)
            ->findByMandatary($mandatary, [
                'date' => 'desc',
                'id' => 'desc',
            ])
        ;

        /**
         * Statistiques calculées à la volée.
         *
         * @var array
         */
        $processedStats = $this->getMandataryHelper()->getProcessedStats($mandatary);

        /**
         * Alertes.
         *
         * @var string[]
         */
        $alerts = $this->getMandataryHelper()->getAlerts($mandatary);

        /**
         * Les cookies à créer.
         *
         * L'affichage des alertes est conditionné par la non existence d'un cookie.
         * Ce cookie est unique par négociateur et par alerte
         * et a une durée de vie limitée pour permettre aux alertes de se réafficher périodiquement.
         *
         * @var Cookie[]
         */
        $cookies = [];

        // Définit un flashbag pour chaque alerte à afficher
        foreach ($alerts as $alertKey => $alert) {
            // Clé unique par négociateur et par alerte
            $cookieKey = sprintf('alert_%s_%s', $mandatary->getId(), $alertKey);

            if (!$request->cookies->has($cookieKey)) {
                // La valeur du cookie ne doit pas être vide
                $cookies[] = Cookie::create($cookieKey, ' ', new \DateTime('+15 minutes'));
                $this->addFlash('warning', $alert);
            }
        }

        // Message d'avertissement pour les tests
        $env = $this->getParameter('kernel.environment');
        if ('preprod' === $env) {
            $this->addFlash('warning', $this->getTranslator()->trans("Mode %env% : attention, vos actions peuvent impacter le négociateur (SMS, agenda…).", ['%env%' => $env]));
        }

        // @var Response
        $response = $this->render(sprintf('mandatary/show.%s.html.twig', $networkId), [
            'mandatary' => $mandatary,
            'mandatary_form' => $mandataryForm->createView(),
            'indicators_forms' => $indicatorsForms,
            'indicators_config' => $indicatorsConfig,
            'events' => $events,
            'events_config' => $eventsConfig,
            'events_forms' => $eventsForms,
            'events_filters' => $eventsFilters,
            'processed_stats' => $processedStats,
        ]);

        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    /**
     * Création et envoi d'un e-mailing.
     *
     * @Route("/negociateurs/e-mailing", name="mandatary_emailing")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function emailing(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();

        $coach = $this->getUser();
        $mandataries = $em->getRepository(Mandatary::class)->findBy([
            'network' => $this->getNetworkHelper()->getNetworkId(),
            'enabled' => true,
        ]);

        // Redéfinit les clés du tableau $mandataries pour mettre les adresses e-mail
        foreach ($mandataries as $key => $mandatary) {
            $email = $mandatary->getEmail();
            $mandataries[$email] = $mandatary;
            unset($mandataries[$key]);
        }

        // Formulaire d'édition du négociateur
        $form = $this->createForm(
            EmailingType::class,
            [
                'mandataries' => $request->get('mandataries', []),
            ],
            [
                'mandataries' => array_values($mandataries),
            ]
        );

        // Traitement du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $clickedButtonName = $form->getClickedButton()->getName();

            switch ($clickedButtonName) {
                case 'test':
                    $subject = $this->fillPlaceholders($data['subject'], $coach);
                    $content = $this->fillPlaceholders($data['content'], $coach);

                    $sent = $this->emailHelper->send(
                        $subject,
                        $content,
                        $coach->getEmail(),
                        (string) $coach,
                        $coach->getEmail(),
                        (string) $coach,
                        $this->getNetworkHelper()->getNetworkId(),
                        'email/default.%s.html.twig',
                        [
                            'sender_phone' => $coach->getPhone(),
                        ]
                    );

                    if ($sent) {
                        $this->addFlash('success', $this->getTranslator()->trans("E-mailing de test envoyé à %email%.", ['%email%' => $coach->getEmail()]));
                    } else {
                        $this->addFlash('error', $this->getTranslator()->trans("Échec de l'envoi de l'e-mailing de test à %email%.", ['%email%' => $coach->getEmail()]));
                    }
                    break;
                case 'send':
                    foreach ($data['mandataries'] as $email) {
                        $mandatary = $mandataries[$email];
                        $subject = $this->fillPlaceholders($data['subject'], $mandatary);
                        $content = $this->fillPlaceholders($data['content'], $mandatary);

                        $sent = $this->emailHelper->send(
                            $subject,
                            $content,
                            $coach->getEmail(),
                            (string) $coach,
                            $mandatary->getEmail(),
                            (string) $mandatary,
                            $this->getNetworkHelper()->getNetworkId(),
                            'email/default.%s.html.twig',
                            [
                                'sender_phone' => $coach->getPhone(),
                            ]
                        );

                        if ($sent) {
                            $event = (new EmailingEvent())
                                ->setMandatary($mandatary)
                                ->setCoach($coach)
                                ->setSubject($subject)
                                ->setContent($content)
                            ;

                            $em->persist($event);
                        } else {
                            $this->addFlash('error', $this->getTranslator()->trans("E-mailing non envoyé à %email%.", ['%email%' => $mandatary->getEmail()]));
                        }
                    }

                    $em->flush();

                    $this->addFlash('success', $this->getTranslator()->trans("E-mailing envoyé."));
                    break;
            }

            if ('send' === $clickedButtonName) {
                return $this->redirectToRoute('mandatary_emailing');
            }
        }

        return $this->render(sprintf('mandatary/emailing.%s.html.twig', $this->getNetworkHelper()->getNetworkId()), [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Dynamise les placeholders contenus dans une chaîne de caractères.
     *
     * @param string                  $string
     * @param ImportableUserInterface $user
     *
     * @return string
     */
    private function fillPlaceholders(string $string, ImportableUserInterface $user): string
    {
        $string = str_replace(self::EMAILING_PLACEHOLDER_FIRST_NAME, $user->getFirstName(), $string);
        $string = str_replace(self::EMAILING_PLACEHOLDER_LAST_NAME, $user->getLastName(), $string);

        return $string;
    }

    /**
     * @return MandataryHelper
     */
    private function getMandataryHelper(): MandataryHelper
    {
        return $this->mandataryHelper;
    }
}
