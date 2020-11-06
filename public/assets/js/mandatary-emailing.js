$(document).ready(function() {

    var $form = $("form[name=\"emailing\"]"),
        $select = $("#emailing_mandataries", $form), // le select des négociateurs
        $counter = $select.closest(".card-body").find(".toolbar__label span"); // le compteur de négociateurs sélectionnés

    // Select2
    $select.select2(defaultSelect2Settings);

    // Mise à jour du compteur
    $select.on("change", function() {
        var count = $select.select2("data").length;
        $counter.html(count);
    });

    // Mise à jour du compteur dès le chargement de la page
    $select.trigger("change");

    $form.on("submit", function() {
        return confirm(messages.please_confirm);
    });
});
