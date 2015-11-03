function debounce(fn, delay) {
    var timer = null;
    return function () {
        var context = this, args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}

(function ($) {
    "use strict";

    $(function () {
        $.nette.init();
    })
})(window.jQuery);