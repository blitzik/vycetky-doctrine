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

function checkAllFunc()
{
    $("#checkAll").change(function () {
        $(".itemToCheck").prop('checked', $(this).prop("checked"));
    });
}

$(function () {
    $.nette.init();
});