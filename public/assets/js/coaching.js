/**
 * Settings par défaut des notifications.
 *
 * @see http://bootstrap-notify.remabledesigns.com/
 */
var defaultNotificationsSettings = {
    type: "inverse",
    placement: {
        from: "top",
        align: "right"
    },
    allow_dismiss: true,
    animate: {
        enter: "animated bounceInRight",
        exit: "animated bounceOutRight"
    },
    template:   '<div data-notify="container" class="alert alert-dismissible alert-{0} alert--notify" role="alert">' +
                    '<span data-notify="icon"></span> ' +
                    '<span data-notify="title">{1}</span> ' +
                    '<span data-notify="message">{2}</span>' +
                    '<div class="progress" data-notify="progressbar">' +
                        '<div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
                    '</div>' +
                    '<a href="{3}" target="{4}" data-notify="url"></a>' +
                    '<button type="button" aria-hidden="true" data-notify="dismiss" class="close"><span>×</span></button>' +
                '</div>'
};

/**
 * Settings par défaut pour Select2.
 *
 * @see https://select2.org/
 */
var defaultSelect2Settings = {
    dropdownAutoWidth: !0,
    width: "100%"
};

/**
 * Settings par défaut pour Flackpickr.
 *
 * @see https://flatpickr.js.org/
 */
var defaultFlatpickrSettings = {
    nextArrow: '<i class="zmdi zmdi-long-arrow-right" />',
    prevArrow: '<i class="zmdi zmdi-long-arrow-left" />',
    locale: 'fr',
    time_24hr: true,
    defaultHour: 9,
    dateFormat: 'd/m/Y H:i',
    altFormat: 'd/m/Y H:i',
    ariaDateFormat: 'd/m/Y H:i',
};

/**
 * Settings par défaut pour DataTables.
 *
 * @see https://datatables.net/
 */
var defaultDataTableSettings = {
    autoWidth: !1,
    responsive: !0,
    lengthMenu: [
        [15, 30, 45, -1],
        ["15 lignes", "30 lignes", "45 lignes", "Voir tout"]
    ],
    language: {
        "sEmptyTable":     "Aucune donnée disponible dans le tableau",
        "sInfo":           "Affichage de l'élément _START_ à _END_ sur _TOTAL_ éléments",
        "sInfoEmpty":      "Affichage de l'élément 0 à 0 sur 0 élément",
        "sInfoFiltered":   "(filtré à partir de _MAX_ éléments au total)",
        "sInfoPostFix":    "",
        "sInfoThousands":  ",",
        "sLengthMenu":     "Afficher _MENU_ éléments",
        "sLoadingRecords": "Chargement…",
        "sProcessing":     "Traitement…",
        "sSearch":         "Rechercher :",
        "sZeroRecords":    "Aucun élément correspondant trouvé",
        "oPaginate": {
            "sFirst":    "Premier",
            "sLast":     "Dernier",
            "sNext":     "Suivant",
            "sPrevious": "Précédent"
        },
        "oAria": {
            "sSortAscending":  ": activer pour trier la colonne par ordre croissant",
            "sSortDescending": ": activer pour trier la colonne par ordre décroissant"
        },
        "select": {
                "rows": {
                    "_": "%d lignes sélectionnées",
                    "0": "Aucune ligne sélectionnée",
                    "1": "1 ligne sélectionnée"
                }
        }
    },
    sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">'
};
