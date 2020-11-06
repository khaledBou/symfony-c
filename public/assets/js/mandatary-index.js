$(document).ready(function() {

    // Changement d'état du bouton "+ de filtres"
    var $moreFilters = $("#more-filters"),
        $showMoreFiltersBtn = $("#show-more-filters");
    $moreFilters.on("shown.bs.collapse", function() {
        $showMoreFiltersBtn.html('<i class="zmdi zmdi-minus-circle-outline"></i> moins de filtres');
    });
    $moreFilters.on("hidden.bs.collapse", function() {
        $showMoreFiltersBtn.html('<i class="zmdi zmdi-plus-circle-o"></i> plus de filtres');
    });
    $showMoreFiltersBtn.on("click", function() {
        $moreFilters.collapse("toggle");
    });

    // Mailing à l'ensemble des négociateurs de la liste filtrée
    $("body").on("click", "[data-table-action=\"emailing\"]", function(e) {
        e.preventDefault();

        var emails = [], // les adresses e-mail des négociateurs filtrés
            rows = dataTable.rows({search: "applied"}).data();

        for (r in rows) {
            var row = rows[r];

            if (row[2] !== undefined) {
                var email = row[2].display;
                emails.push(email);
            }
        }

        // Le formulaire à poster
        var form = document.createElement("form");
        form.method = "POST";
        form.action = params.mandatary_emailing_url;
        document.body.appendChild(form);

        // Ses données
        for (e in emails) {
            var email = emails[e],
                input = document.createElement("input");
            input.type = "hidden";
            input.name = "mandataries[]";
            input.value = email;
            form.appendChild(input);
        }

        // Envoi du formulaire
        form.submit();
    });

    /**
     * Indique si la valeur value est comprise entre min et max inclus.
     *
     * @param int value
     * @param int min
     * @param int max
     *
     * @return bool
     */
    function isInRange(value, min, max) {
        var hasMin = null !== min,
            hasMax = null !== max;

        return ( !hasMin && !hasMax ) ||
            ( !hasMin && value <= max ) ||
            ( min <= value && !hasMax ) ||
            ( min <= value && value <= max );
    }

    // Filtres
    var $coachFilter = $(":input[name=filters_coach]"), // Coach
        $mandataryFilter = $(":input[name=filters_mandatary]"), // Négociateur
        $animatorFilter = $(":input[name=filters_animator]"), // Animateur
        $supportStatusFilter = $(":input[name=filters_support_status]"), // État de suivi
        $careLevelFilter = $(":input[name=filters_care_level]"), // Besoin d'accompagnement
        $potentialFilter = $(":input[name=filters_potential]"), // Potentiel commercial
        $autonomePublicationFilter = $(":input[name=filters_autonome_publication]"), // Autonomie en publication
        $beginDateFromFilter = $(":input[name=filters_begin_date_from]"), // Date d'entrée dans le réseau (à partir de)
        $beginDateToFilter = $(":input[name=filters_begin_date_to]"), // Date d'entrée dans le réseau (jusqu'à)
        $tradesCountFilter = $(":input[name=filters_trades_count]"), // Nombre de mandats
        $compromisesCountFilter = $(":input[name=filters_compromises_count]"), // Nombre de compromis
        $salesCountFilter = $(":input[name=filters_sales_count]"), // Nombre de ventes
        $tradeShortfallFilter = $(":input[name=filters_trade_shortfall]"), // Absence de mandat récent
        $compromiseShortfallFilter = $(":input[name=filters_compromise_shortfall]"), // Absence de compromis récent
        $saleShortfallFilter = $(":input[name=filters_sale_shortfall]"); // Absence de vente récente

    // Select2 sur les champs animator et coach @see https://stackoverflow.com/questions/29618382/disable-dropdown-opening-on-select2-clear#answer-35807365
    $animatorFilter.add($coachFilter).select2(defaultSelect2Settings).on("select2:unselecting", function() {
        $(this).data("unselecting", true);
    }).on("select2:opening", function(e) {
        if ($(this).data("unselecting")) {
            $(this).removeData("unselecting");
            e.preventDefault();
        }
    });

    // Branchement de la recherche native sur le champ coach
    $coachFilter.on("change", function() {
        var value = $(this).val();
        dataTable.column(7).search(null === value ? '' : value).draw(); // recherche native sur le coach uniquement
    });

    // Branchement de la recherche native sur le champ négociateur
    $mandataryFilter.on("input", function() {
        var value = $(this).val();
        dataTable.search(value).draw(); // recherche native sur toutes les colonnes marquées comme searchable
    });

    // Branchement de la recherche native sur le champ animateur
    $animatorFilter.on("change", function() {
        var value = $(this).val();
        dataTable.column(6).search(null === value ? '' : value).draw(); // recherche native sur l'animateur uniquement
    });

    // Branchement de la recherche custom sur les champs d'état de suivi, de besoin d'accompagnement et de potentiel commercial
    $supportStatusFilter.add($careLevelFilter).add($potentialFilter).on("change", function() {
        dataTable.draw(); // recherche custom
    });

    // Branchement de la recherche custom sur le champ d'autonomie en publication
    $autonomePublicationFilter.on("change", function() {
        dataTable.draw(); // recherche custom
    });

    // Branchement de la recherche custom sur les champs de date d'entrée dans le réseau
    $beginDateFromFilter.add($beginDateToFilter).on("input", function() {
        var $this = $(this),
            value = $this.val();
        if (10 === value.length) {
            dataTable.draw(); // recherche custom
        }
    });
    $beginDateFromFilter.add($beginDateToFilter).on("blur", function() {
        var $this = $(this),
            value = $(this).val();
        if ('' !== value && !moment(value, 'DD/MM/YYYY').isValid()) {
            $this.val('');
        }
    });

    // Branchement de la recherche custom sur les champs nombre de mandats, de compromis et de ventes
    $tradesCountFilter.add($compromisesCountFilter).add($salesCountFilter).on("change", function() {
        dataTable.draw(); // recherche custom
    });

    // Branchement de la recherche custom sur les champs d'absence de mandat, de compromis et de vente récents
    $tradeShortfallFilter.add($compromiseShortfallFilter).add($saleShortfallFilter).on("change", function() {
        dataTable.draw(); // recherche custom
    });

    // Recherche custom sur l'état de suivi
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var value = $supportStatusFilter.filter(":checked").val();

        return undefined === value || '' === value ? true : rawData[9]['@data-search'] === value;
    });

    // Recherche custom sur le besoin d'accompagnement
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var value = $careLevelFilter.filter(":checked").val();

        return undefined === value || '' === value ? true : rawData[10]['@data-search'] === value;
    });

    // Recherche custom sur le potentiel commercial
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var value = $potentialFilter.filter(":checked").val();

        return undefined === value || '' === value ? true : rawData[11]['@data-search'] === value;
    });

    // Recherche custom sur l'autonomie en publication
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var checked = $autonomePublicationFilter.is(":checked");

        return checked ? "yes" === rawData[12]['@data-search'] : true;
    });

    // Recherche custom sur la date d'entrée dans le réseau
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var beginDateFrom = moment($beginDateFromFilter.val(), 'DD/MM/YYYY'),
            beginDateTo = moment($beginDateToFilter.val(), 'DD/MM/YYYY'),
            value = parseInt(rawData[4]['@data-search'], 10);

        return isInRange(
            value,
            beginDateFrom.isValid() ? beginDateFrom.unix() : null,
            beginDateTo.isValid() ? beginDateTo.unix() : null,
        );
    });

    // Recherche custom sur le nombre de mandats
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var values = $tradesCountFilter.filter(":checked").val().split("-"),
            min = parseInt(values[0], 10),
            max = parseInt(values[1], 10),
            value = parseInt(rawData[13]['@data-search'], 10);

        return isInRange(
            value,
            isNaN(min) ? null : min,
            isNaN(max) ? null : max,
        );
    });

    // Recherche custom sur le nombre de compromis
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var values = $compromisesCountFilter.filter(":checked").val().split("-"),
            min = parseInt(values[0], 10),
            max = parseInt(values[1], 10),
            value = parseInt(rawData[14]['@data-search'], 10);

        return isInRange(
            value,
            isNaN(min) ? null : min,
            isNaN(max) ? null : max,
        );
    });

    // Recherche custom sur le nombre de ventes
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var values = $salesCountFilter.filter(":checked").val().split("-"),
            min = parseInt(values[0], 10),
            max = parseInt(values[1], 10),
            value = parseInt(rawData[15]['@data-search'], 10);

        return isInRange(
            value,
            isNaN(min) ? null : min,
            isNaN(max) ? null : max,
        );
    });

    // Recherche custom sur l'absence de mandat récent
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var checked = $tradeShortfallFilter.is(":checked");

        return checked ? "yes" === rawData[16]['@data-search'] : true;
    });

    // Recherche custom sur l'absence de compromis récent
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var checked = $compromiseShortfallFilter.is(":checked");

        return checked ? "yes" === rawData[17]['@data-search'] : true;
    });

    // Recherche custom sur l'absence de vente récente
    $.fn.dataTable.ext.search.push(function(settings, data, index, rawData) {
        var checked = $saleShortfallFilter.is(":checked");

        return checked ? "yes" === rawData[18]['@data-search'] : true;
    });

    // Initialisation de DataTable
    var dataTable = $("table").DataTable($.extend({}, defaultDataTableSettings, {
        order: [ // Tri par défaut
            [ 9, "asc" ], // état de suivi
            [ 10, "asc" ], // besoin d'accompagnement
            [ 11, "desc" ], // potentiel commercial
            [ 8, "asc" ], // nombre de contacts avec le coach
        ],
        columnDefs: [
            {
                targets: [
                    0, // civilité
                ],
                searchable: false
            },
            {
                targets: [
                    0, // civilité
                    2, // e-mail
                    7, // coach
                    12, // autonomie en publication
                    16, // absence de mandat récent
                    17, // absence de compromis récent
                    18, // absence de vente récente
                ],
                visible: false,
            }
        ],
        lengthMenu: [
            [50, 100, 500, -1],
            ["50 négociateurs", "100 négociateurs", "500 négociateurs", "Tous"]
        ],
        language: {
            "sEmptyTable":     "Aucun négociateur",
            "sInfo":           "Affichage du négociateur _START_ à _END_ sur un total de _TOTAL_",
            "sInfoEmpty":      "Affichage du négociateur 0 à 0 sur 0 négociateur",
            "sInfoFiltered":   "(filtré à partir de _MAX_ négociateurs au total)",
            "sInfoPostFix":    "",
            "sInfoThousands":  ",",
            "sLengthMenu":     "Afficher _MENU_ négociateurs",
            "sLoadingRecords": "Chargement…",
            "sProcessing":     "Traitement…",
            "sSearch":         "Rechercher :",
            "sZeroRecords":    "Aucun négociateur trouvé",
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
                        "_": "%d négociateurs sélectionnés",
                        "0": "Aucune négociateur sélectionné",
                        "1": "1 négociateur sélectionné"
                    }
            }
        },
        sDom: '<"dataTables__top"lB>rt<"dataTables__bottom"ip><"clear">',
        aoSearchCols: [ // Recherche immédiate sur le coach dès que le tableau est chargé
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            { "sSearch": $coachFilter.val() },
        ],
        // Inspiré de @see https://datatables.net/examples/advanced_init/footer_callback.html
        drawCallback: function (row, data, start, end, display) {

            // Les colonnes pour lesquelles afficher le total
            var cols = [
                13, // mandats
                14, // compromis
                15, // ventes
            ];

            for (var c in cols) {
                var col = cols[c],
                    column = this.api().column(col);

                // Total de toutes les pages
                var total = column
                    .data()
                    .reduce(function (a, b) {
                        return parseInt(a, 10) + parseInt(b, 10);
                    }, 0)
                ;

                // Total de la page courante
                var pageTotal = this
                    .api()
                    .column(col, {page: "current"})
                    .data()
                    .reduce(function (a, b) {
                        return parseInt(a, 10) + parseInt(b, 10);
                    }, 0)
                ;

                var title = $(column.header()).data("title"),
                    $header = $(column.header()),
                    $footer = $(column.footer());

                // Mise à jour des header et footer de la colonne
                $header.add($footer).html(
                    `${title} (${pageTotal}/${total})`
                );

                // Force l'activation des tooltips
                $('[data-toggle="tooltip"]')[0] && $('[data-toggle="tooltip"]').tooltip();
            }
        },
        initComplete: function () {
            $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend(
                '<div class="dataTables_buttons hidden-sm-down actions">' +
                    '<span class="actions__item zmdi zmdi-mail-send" data-table-action="emailing" title="' + messages.emailing + '" data-toggle="tooltip" data-placement="left" />' +
                '</div>'
            );
        }
    }));
});
