(function ($) {
    "use strict";

    $(function () {
        var pass = $('#password-input');
        var passMessage = $('#pass-message');
        var passControl = $('#password-control-input');
        var controlMessage = $('#pass-control-message');

        var saveButton = $('#password-save-button');
        saveButton.attr('disabled', true).css('opacity', '0.5');

        var arePasswordsEqual = function (pass, pass2) {
            if (pass != pass2) {
                controlMessage.text('Hesla se neshodují').css('color', '#F2B6B6');
                saveButton.attr('disabled', true).css('opacity', '0.5');
            } else {
                controlMessage.text('Hesla souhlasí').css('color', '#72D62F');
                saveButton.attr('disabled', false).css('opacity', '1');
            }
        };

        pass.on('keyup', function () {
            var self = $(this);
            if (self.val().length < 5) {
                passMessage.text('Heslo musí mít nejméně 5 znaků');
                controlMessage.text('Kontrola hesla:').css('color', '');
                saveButton.attr('disabled', true).css('opacity', '0.5');
            } else {
                passMessage.text('Uživatelské heslo:').css('color', '');
                if (passControl.val().length > 0) {
                    arePasswordsEqual($(this).val(), passControl.val());
                }
            }
        });

        passControl.on('keyup', function () {
            var self = $(this);

            if (pass.val().length >= 5) {
                arePasswordsEqual(pass.val(), self.val());
            }
        });
    });

} (window.jQuery));