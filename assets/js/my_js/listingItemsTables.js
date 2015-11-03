(function ($) {
    "use strict";

    $(function () {
        var detailTable = $('#listing-table-content-block');

        detailTable.on('change', '#checkAll', function (e) {
            $(".itemToCheck").prop('checked', $(this).prop("checked"));
        });

        detailTable.on('click', 'tr', function (e) {
            var checkbox = $('.itemToCheck', this);

            var initialState = checkbox.prop('checked');
            if (e.target && e.target.nodeName == 'INPUT') {
                initialState = !initialState;
            }

            checkbox.prop('checked', !initialState);
        });
    });

    // LISTINGS MERGING
    var mergingDetailTable = $('#listing-merging-table-content-block');
    mergingDetailTable.on('click', '.itemToCheck', function () {
        var otherItemID = this.dataset.other;
        var otherItem = $('#rowID-' + otherItemID);
        if (this.checked === true) {
            otherItem.find('input[type="checkbox"]').prop('disabled', true);
            otherItem.fadeOut(400);
        } else {
            otherItem.find('input[type="checkbox"]').prop('disabled', false);
            otherItem.fadeIn(400);
        }
    });

})(window.jQuery);