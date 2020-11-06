$(document).ready(function() {

    // Variables globales à ce script
    var $notifications, // l'élément de liste des notifications
        $showNotificationsBtn, // bouton d'ouverture du dropdown des notifications
        $deleteNotificationsBtn, // bouton de suppression des notifications déjà lues
        $clearNotificationsBtn; // bouton de marquage des notification comme lues

    /**
     * Permet de définir et redéfinir les variables globales ainsi que leurs listeners,
     * au chargement de la page et après un rechargement ajax du DOM.
     */
    function setVariablesAndListeners() {

        $notifications = $("#notifications");
        $showNotificationsBtn = $("#show-notifications");
        $deleteNotificationsBtn = $("#delete-notifications");
        $clearNotificationsBtn = $("#clear-notifications");

        /**
         * Marquage d'une notification comme lue,
         * au click sur l'une d'entre elles.
         */
        $(".top-nav__notifications .listview__item").off("click").on("click", function(e) {
            // Empêche la fermeture du dropdown des notifications
            e.stopPropagation();

            // La notification
            var $notification = $(this);

            // Marque la notification comme lue
            $notification.removeClass("unread").addClass("read");

            // Affiche le bouton pour supprimer les notifications déjà lues
            $deleteNotificationsBtn.removeClass("hidden");

            /* Supprime le point clignotant au dessus de l'icône des notifications
            * et masque le bouton pour marquer les notifications comme lues,
            * s'il n'y a plus de notifications non lues. */
            if (0 === $(".top-nav__notifications .listview__item.unread").length) {
                $showNotificationsBtn.removeClass("top-nav__notify");
                $clearNotificationsBtn.addClass("hidden");
            }
        });

        /**
         * Marquage de toutes les notifications comme lues,
         * au click sur le bouton.
         *
         * Les animations ont été inspirées de public/assets/js/inc/actions.js (chercher 'notifications-clear').
         */
        $clearNotificationsBtn.off("click").on("click", function(e) {
            e.preventDefault();

            // Empêche la fermeture du dropdown des notifications
            e.stopPropagation();

            // Les notifications non lues
            var $unreadNotifications = $(".top-nav__notifications .listview__item.unread");

            // Masque le bouton
            $clearNotificationsBtn.addClass("hidden");

            var offset = 0, // décalage en ms, pour les timeouts de début des animations
                delay = 150, // délai en ms, entre l'animation de chaque notification
                timeouts = []; // permet de stocker tous les timeouts pour les annuler en cas d'erreur, et ainsi stopper les animations

            // Anime successivement chaque notification en la marquant comme lue
            $unreadNotifications.each(function() {
                var $notification = $(this);
                timeouts.push(setTimeout(function() {
                    $notification.removeClass("unread").addClass("read");
                }, offset += delay));
            });

            // Requête ajax
            $.ajax({
                type: "GET",
                url: $clearNotificationsBtn.attr("href"),
                success: function() {
                    // Force le marquage des notifications comme lues
                    $unreadNotifications.removeClass("unread").addClass("read");

                    // Affiche le bouton pour supprimer les notifications déjà lues
                    $deleteNotificationsBtn.removeClass("hidden");

                    /* Supprime le point clignotant au dessus de l'icône des notifications
                    * s'il n'y a plus de notifications non lues. */
                    if (0 === $(".top-nav__notifications .listview__item.unread").length) {
                        $showNotificationsBtn.removeClass("top-nav__notify");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Stoppe les animations
                    for (var t in timeouts) {
                        clearTimeout(timeouts[t]);
                    }

                    // Annule les animations par lesquelles les notifications avaient été marquées comme lues
                    $unreadNotifications.removeClass("read").addClass("unread");

                    // Réaffiche le bouton
                    $clearNotificationsBtn.removeClass("hidden");

                    // Affiche un message d'erreur
                    $.notify({
                        message: "unauthorized" === errorThrown.toLowerCase() ? messages.notifications_not_marked_as_read_logged_out : messages.notifications_not_marked_as_read
                    }, $.extend({}, defaultNotificationsSettings, {
                        delay: 1000,
                    }));
                }
            });
        });

        /**
         * Suppression des notifications déjà lues,
         * au click sur le bouton.
         *
         * Les animations ont été reprises de public/assets/js/inc/actions.js (chercher 'notifications-clear').
         */
        $deleteNotificationsBtn.off("click").on("click", function(e) {
            e.preventDefault();

            // Empêche la fermeture du dropdown des notifications
            e.stopPropagation();

            // Les notifications déjà lues
            var $readNotifications = $(".top-nav__notifications .listview__item.read");

            // Masque le bouton
            $deleteNotificationsBtn.addClass("hidden");

            var offset = 0, // décalage en ms, pour les timeouts de début des animations
                delay = 150, // délai en ms, entre l'animation de chaque notification
                timeouts = []; // permet de stocker tous les timeouts pour les annuler en cas d'erreur, et ainsi stopper les animations

            // Anime successivement chaque notification en la faisant partir vers la droite
            $readNotifications.each(function() {
                var $notification = $(this);
                timeouts.push(setTimeout(function() {
                    $notification.addClass("animated fadeOutRight");
                }, offset += delay));
            });

            // Requête ajax
            $.ajax({
                type: "GET",
                url: $deleteNotificationsBtn.attr("href"),
                success: function() {
                    // Supprime les notifications du DOM
                    $readNotifications.remove();

                    /* Supprime le point clignotant au dessus de l'icône des notifications
                    * s'il n'y a plus de notifications non lues. */
                    if (0 === $(".top-nav__notifications .listview__item.unread").length) {
                        $showNotificationsBtn.removeClass("top-nav__notify");
                    }

                    /* Affiche une icône "checked" en lieu et place des notifications
                    * s'il n'y en a plus. */
                    if (0 === $(".top-nav__notifications .listview__item").length) {
                        $(".top-nav__notifications").addClass("top-nav__notifications--cleared");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Stoppe les animations
                    for (var t in timeouts) {
                        clearTimeout(timeouts[t]);
                    }

                    // Annule les animations par lesquelles les notifications étaient parties vers la droite
                    $readNotifications.removeClass("animated fadeOutRight");

                    // Réaffiche le bouton
                    $deleteNotificationsBtn.removeClass("hidden");

                    // Affiche un message d'erreur
                    $.notify({
                        message: "unauthorized" === errorThrown.toLowerCase() ? messages.notifications_not_deleted_logged_out : messages.notifications_not_deleted
                    }, $.extend({}, defaultNotificationsSettings, {
                        delay: 1000,
                    }));
                }
            });
        });
    }

    // Définit les variables globales et leurs listeners
    setVariablesAndListeners();

    /**
     * Rechargement ajax des notifications, toutes les 5 minutes.
     */
    setInterval(function() {
        // Requête ajax
        $.ajax({
            type: "GET",
            url: params.notifications_url,
            success: function(html) {
                var $html = $(html);

                // Les notifications étaient-elles affichées ?
                var dropdownWasVisible = $notifications.hasClass("show");

                if ($html.is("li#notifications")) {
                    $notifications.replaceWith(html);

                    // Redéfinit les variables globales et leurs listeners
                    setVariablesAndListeners();

                    // Force l'activation des tooltips
                    $('[data-toggle="tooltip"]')[0] && $('[data-toggle="tooltip"]').tooltip();

                    // Réaffiche éventuellement les notifications
                    dropdownWasVisible && $showNotificationsBtn.dropdown("toggle");
                } else {
                    // Affiche un message d'erreur
                    $.notify({
                        message: messages.notifications_not_loaded
                    }, $.extend({}, defaultNotificationsSettings, {
                        delay: 1000,
                    }));
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Affiche un message d'erreur
                $.notify({
                    message: "unauthorized" === errorThrown.toLowerCase() ? messages.notifications_not_loaded_logged_out : messages.notifications_not_loaded
                }, $.extend({}, defaultNotificationsSettings, {
                    delay: 1000,
                }));
            }
        });
    }, 300000);
});
