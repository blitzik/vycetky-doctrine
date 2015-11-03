(function () {
    "use strict";

    $(function () {
        var workedHours = $('#workedHours');
        workedHours.text(workedHours.data('workedhours'));

        var locality = $('#locality');
        locality.autocomplete({
            source: locality.data('autocomplete'),
            delay: 500,
            minLength: 3
        });
    });


})(window.jQuery);