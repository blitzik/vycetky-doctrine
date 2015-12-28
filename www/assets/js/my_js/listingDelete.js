(function ($) {
    $(function () {
        var deleteButton = $('#listing-remove-button');
        var checkInput = $('#listing-check-input');

        deleteButton.attr('disabled', true);

        checkInput.keyup(function () {
            var self = $(this);
            if (self.val() == 'smazat') {
                deleteButton.attr('disabled', false);
            } else {
                deleteButton.attr('disabled', true);
            }
        });

    });
})(window.jQuery);