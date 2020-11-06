<?php

namespace App\Controller;

use App\Entity\Notification\Notification;
use App\Service\NetworkHelper;
use App\Service\NotificationHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour les notifications.
 */
class NotificationController extends AbstractController
{
    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    /**
     * @param NetworkHelper       $networkHelper
     * @param TranslatorInterface $translator
     * @param EventHelper         $notificationHelper
     */
    public function __construct(NetworkHelper $networkHelper, TranslatorInterface $translator, NotificationHelper $notificationHelper)
    {
        parent::__construct($networkHelper, $translator);

        $this->notificationHelper = $notificationHelper;
    }

    /**
     * Construit l'élément de liste des notifications,
     * destinée à être affichée dans le header.
     *
     * @Route("/notifications/li", name="notification_li")
     *
     * @return Response
     */
    public function li(): Response
    {
        $user = $this->getUser();

        return $this->render(sprintf('notification/li.%s.html.twig', $this->getNetworkHelper()->getNetworkId()), [
            'has_notifications' => $this->notificationHelper->hasNotifications($user, null),
            'has_read_notifications' => $this->notificationHelper->hasNotifications($user, true),
            'has_unread_notifications' => $this->notificationHelper->hasNotifications($user, false),
            'notifications' => $this->notificationHelper->getNotifications($user, 50),
        ]);
    }

    /**
     * Consulte une notification,
     * et la marque comme lue.
     *
     * @param Notification $notification
     *
     * @Route("/notification/{id}", name="notification_show")
     *
     * @return Response
     *
     * @throws NotFoundHttpException Lorsque la notification n'appartient pas à l'utilisateur connecté
     */
    public function show(Notification $notification): Response
    {
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        if (!$notification->isRead()) {
            $notification->setRead(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($notification);
            $em->flush();
        }

        return new RedirectResponse($notification->getUrl());
    }

    /**
     * Marquage ajax des notifications de l'utilisateur courant comme lues.
     *
     * @Route("/notifications/marquer-comme-lues", name="notifications_mark_as_read", condition="request.isXmlHttpRequest()")
     *
     * @return Response
     */
    public function markAsRead(): Response
    {
        // Utilisation d'une native query pour des raisons de performance lorsqu'il y a énormément de notifications
        $this
            ->getDoctrine()
            ->getManager()
            ->getConnection()
            ->prepare('UPDATE notification SET read = true WHERE user_id = :user_id')
            ->execute([
                'user_id' => $this->getUser()->getId(),
            ])
        ;

        return new Response();
    }

    /**
     * Suppression ajax des notifications déjà lues de l'utilisateur courant.
     *
     * @Route("/notifications/supprimer", name="notifications_delete", condition="request.isXmlHttpRequest()")
     *
     * @return Response
     */
    public function delete(): Response
    {
        // Utilisation d'une native query pour des raisons de performance lorsqu'il y a énormément de notifications
        $this
            ->getDoctrine()
            ->getManager()
            ->getConnection()
            ->prepare('DELETE FROM notification WHERE user_id = :user_id AND read = true')
            ->execute([
                'user_id' => $this->getUser()->getId(),
            ])
        ;

        return new Response();
    }
}
