$(document).ready(function() {

    var $loader = $(".page-loader");

    // Filtres sur les types d'événements
    var $eventsFilters = $("[data-filter-event-type]"),
        $events = $("[data-event-type]");

    // Les types d'événements sélectionnés
    var selectedEventsTypes = [];
    $eventsFilters.filter("[data-filter-event-type-selected]").each(function() {
        var $eventsFilter = $(this),
            eventsType = $eventsFilter.data("filter-event-type");
        selectedEventsTypes.push(eventsType);
    });

    // Click sur un des filtres
    $eventsFilters.on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Le type d'événement à toggler
        var $eventsFilter = $(this),
            $eventsFilterIcon = $("i.zmdi", $eventsFilter),
            eventsType = $eventsFilter.data("filter-event-type");

        // Ajoute ou supprime le type d'événement de la liste des types d'événements sélectionnés
        var pos = selectedEventsTypes.indexOf(eventsType);
        if (pos > -1) {
            selectedEventsTypes.splice(pos, 1);
            $eventsFilterIcon.removeClass("zmdi-check");
        } else {
            selectedEventsTypes.push(eventsType);
            $eventsFilterIcon.addClass("zmdi-check");
        }

        // Filtrage
        var selector = '[data-event-type*="' + selectedEventsTypes.join('"], [data-event-type*="') + '"]',
            $filteredEvents = $events.filter(selector);
        $events.not($filteredEvents).addClass("hidden");
        $filteredEvents.removeClass("hidden");
    });

    // Changement d'état des champs de formulaire ajax
    $(":input", "form[name=mandatary_form], form[name$=_indicator_form]").on("change", function() {
        // @todo Ajouter le support des flashbag pour les requêtes Ajax
        var $ajaxInput = $(this),
            $ajaxForm = $ajaxInput.closest("form"),
            formAction = $ajaxForm.attr("action"),
            formData = $ajaxForm.serializeArray(),
            notificationSettings = $.extend({}, defaultNotificationsSettings, {
                delay: 3000,
            });

        $.ajax({
            type: "POST",
            url: formAction,
            data: formData,
            success: function() {
                $.notify({
                    message: messages.changes_saved
                }, notificationSettings);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $.notify({
                    message: "unauthorized" === errorThrown.toLowerCase() ? messages.changes_not_saved_logged_out : messages.changes_not_saved
                }, notificationSettings);
            }
        });
    });

    // Submit de formulaire ajax, avec support du local storage
    $("form[name$=_event_form]").on("submit", function(e) {
        // @todo Ajouter le support des flashbag pour les requêtes Ajax
        e.preventDefault();

        $loader.fadeIn();

        var $ajaxForm = $(this),
            formAction = $ajaxForm.attr("action"),
            formData = $ajaxForm.serializeArray(),
            notificationSettings = $.extend({}, defaultNotificationsSettings, {
                delay: 3000,
            });

        $.ajax({
            type: "POST",
            url: formAction,
            data: formData,
            success: function() {
                $(":input[data-local-storage-id]", $ajaxForm).each(function() {
                    var $input = $(this),
                        localStorageId = $input.attr("data-local-storage-id");

                    localStorage.removeItem(localStorageId);
                });

                location.reload();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $loader.fadeOut();

                $.notify({
                    message: "unauthorized" === errorThrown.toLowerCase() ? messages.changes_not_saved_logged_out : messages.changes_not_saved
                }, notificationSettings);
            }
        });
    });

    /* Au chargement de la page, si un message d'erreur est affiché dans l'aside de création des événements,
     * alors on affiche cet aside et l'onglet concerné. */
    var $formErrors = $(".invalid-feedback");

    if ($formErrors.length > 0) {
        var $formError = $formErrors.first(),
            $tab = $formError.closest(".tab-pane"); // l'onglet éventuel

        if ($tab.length > 0) {
            var tabId = $tab.attr("id"), // l'id de l'onglet
                $tabTrigger = $("[data-toggle=tab][href=\"#" + tabId + "\"]"), // le bouton d'affichage de l'onglet
                $aside = $tab.closest("aside.new-event"); // l'aside éventuel

            // Affichage de l'onglet
            $tabTrigger.trigger("click");

            // Affichage de l'aside éventuel
            if ($aside.length > 0) {
                $aside.addClass("toggled");
                $(".content, .header").append('<div class="ma-backdrop" data-ma-action="aside-close" data-ma-target=".new-event" />');
            }
        }
    }

    // Modèles pour pré-remplissage des rendez-vous
    var $appointmentModels = $('[name="appointment_event_form[model]"]'), // les modèles de rendez-vous disponibles
        $appointmentDuration = $('[name="appointment_event_form[duration]"]'), // champ "durée"
        $appointmentSubject = $('[name="appointment_event_form[subject]"]'), // champ "objet"
        $appointmentDescription = $('[name="appointment_event_form[description]"]'); // champ "description"

    // Sélection d'un modèle
    $appointmentModels.on("change", function() {
        var $appointmentModel = $(this),
            value = $appointmentModel.val();

        // Si un modèle a été sélectionné
        if ("none" !== value) {
            var values = value.split("|");
                duration = values[0];
                subject = values[1],
                description = values[2],

            // Applique le modèle aux champs concernés
            $appointmentDuration.filter("[value=" + duration + "]").prop("checked", true);
            $appointmentSubject.val(subject);
            $appointmentDescription.val(description);
        } else {
            // Vide les champs qui peuvent l'être
            $appointmentSubject.val('');
            $appointmentDescription.val('');
        }
    });

    // Modèles pour pré-remplissage des relances
    var $mandataryReminderModels = $('[name="mandatary_reminder_event_form[model]"]'), // les modèles de relance disponibles
        $mandataryReminderContent = $('[name="mandatary_reminder_event_form[content]"]'); // champ "texte de la relance"

    // Sélection d'un modèle
    $mandataryReminderModels.on("change", function() {
        var $mandataryReminderModel = $(this),
            value = $mandataryReminderModel.val();

        // Si un modèle a été sélectionné
        if ("none" !== value) {
            var content = value;

            // Applique le modèle aux champs concernés
            $mandataryReminderContent.val(content);
        } else {
            // Vide les champs qui peuvent l'être
            $mandataryReminderContent.val('');
        }
    });

    // Modèles pour pré-remplissage des rappels
    var $coachReminderModels = $('[name="coach_reminder_event_form[model]"]'), // les modèles de rappel disponibles
        $coachReminderContent = $('[name="coach_reminder_event_form[content]"]'); // champ "texte du rappel"

    // Sélection d'un modèle
    $coachReminderModels.on("change", function() {
        var $coachReminderModel = $(this),
            value = $coachReminderModel.val();

        // Si un modèle a été sélectionné
        if ("none" !== value) {
            var content = value;

            // Applique le modèle aux champs concernés
            $coachReminderContent.val(content);
        } else {
            // Vide les champs qui peuvent l'être
            $coachReminderContent.val('');
        }
    });

    // Les champs à sauvegarder dans le local storage
    var $localStorageInputs = $(":input[data-local-storage-id]");

    // Au chargement de la page, récupération des valeurs enregistrées dans le local storage
    $localStorageInputs.each(function() {
        var $input = $(this),
            localStorageId = $input.attr("data-local-storage-id"),
            value = localStorage.getItem(localStorageId);

        if (null !== value) {
            $input.val(value);

            // Applique le textarea autosize
            if ($input.hasClass("textarea-autosize")) {
                // @see http://www.jacklmoore.com/autosize/#faq-hidden
                $input.on("focus", function() {
                    autosize.update($input);
                });
            }
        }
    });

    // À la saisie, enregistrement des valeurs dans le local storage
    $localStorageInputs.on("input", function() {
        var $input = $(this),
            localStorageId = $input.attr("data-local-storage-id"),
            value = $input.val();

        localStorage.setItem(localStorageId, value);
    });
});
