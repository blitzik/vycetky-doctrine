(function ($, tc) {
    "use strict";

    $(function () {
        var STEP = 30; // 30 minutes step

        $('.time-control').css('display', 'block');

        var btnResetTime = $('#btn-reset-time');
        btnResetTime.css('display', 'block');

        // Input fields

        var workStart = $('#workStart');
        var workEnd = $('#workEnd');
        var lunch = $('#lunch');
        var otherHours = $('#otherHours');

        var workedHours = $('#workedHours');

        var timeFieldsGroup = {
            'workStart': {
                'input': workStart,
                'slider_val_position': 0
            },
            'workEnd': {
                'input': workEnd,
                'slider_val_position': 1
            },
            'lunch': {
                'input': lunch
            },
            'otherHours': {
                'input': otherHours
            }
        };

        // Sliders definition

        var slider_lunch = $('#slider-lunch');
        var slider_range = $('#slider-range');
        var slider_time_other = $('#slider-time-other');

        var slidersGroup = {
            'slider_range': slider_range,
            'slider_lunch': slider_lunch,
            'slider_time_other': slider_time_other
        };

        slider_lunch.slider(
            {
                min: 0,
                max: 300,
                step: STEP,
                value: tc.timeWithComma2Minutes(lunch.val()),
                slide: function (event, ui) {
                    var time = ui.value;
                    var wsMinutes = tc.time2Minutes(workStart.val());
                    var weMinutes = tc.time2Minutes(workEnd.val());

                    var workedTime = weMinutes - wsMinutes - ui.value;

                    if (workedTime < 0) {
                        return false;
                    }

                    lunch.val(tc.minutes2TimeWithComma(time));

                    workedHours.text(tc.minutes2TimeWithComma(weMinutes - wsMinutes - ui.value));
                }
            }
        );

        slider_range.slider(
            {
                range: true,
                min: 0,
                max: 1410,
                step: STEP,
                values: [tc.time2Minutes(workStart.val()),
                         tc.time2Minutes(workEnd.val())],
                slide: function (event, ui) {
                    var l = tc.timeWithComma2Minutes(lunch.val());
                    var workedTime = ui.values[1] - ui.values[0] - l;

                    if (workedTime < 0) {
                        return false;
                    }

                    workStart.val(tc.minutes2Time(ui.values[0]));
                    workEnd.val(tc.minutes2Time(ui.values[1]));

                    workedHours.text(tc.minutes2TimeWithComma(workedTime));
                }
            }
        );

        slider_time_other.slider(
            {
                min: 0,
                max: 1410,
                step: STEP,
                value: tc.timeWithComma2Minutes(otherHours.val()),
                slide: function (event, ui) {
                    otherHours.val(tc.minutes2TimeWithComma(ui.value));
                }
            }
        );

        // Sliders times set in item edit. Default values or values from DB

        workStart.change(function () {
            slider_range.slider('values', 0, this.innerHTML);
        });

        workEnd.change(function () {
            slider_range.slider('values', 1, this.innerHTML);
        });

        otherHours.change(function () {
            slider_time_other.slider('value', this.innerHTML);
        });

        // Sliders appearance

        lunch.attr('readonly', true);
        workStart.attr('readonly', true);
        workEnd.attr('readonly', true);
        otherHours.attr('readonly', true);

        btnResetTime.click(function () {
            lunch.val('0');
            workStart.val('0:00');
            workEnd.val('0:00');
            workedHours.text('0');
            otherHours.val('0');

            slider_range.slider('values', 0, 0);
            slider_range.slider('values', 1, 0);
            slider_lunch.slider('value', 0);
            slider_time_other.slider('value', 0);
        });

        // time control buttons

        var buttonAdd = $('.btn-time-control-add');
        var buttonSub = $('.btn-time-control-sub');

        /**
         * @param time
         * @param fnTime Time in HH..:MM format
         * @param fnTimeWithComma Time in format with comma
         * @returns mixed
         */
        var doByTimeFormat = function (time, fnTime, fnTimeWithComma) {
            if (tc.isInTimeFormat(time)) {
                return fnTime(time);
            } else if (tc.isInTimeWithCommaFormat(time)) {
                return fnTimeWithComma(time);
            } else {
                throw 'Unexpected time format!';
            }
        };

        /**
         * @param button jQuery object
         * @param seconds
         */
        function doAction(button, seconds) // TODO - figure out better name
        {
            var time = button.data('time');

            var field = timeFieldsGroup[time.inputID];
            var slider = slidersGroup[time.slider];

            var minutes = tc.toMinutes(field.input.val());
            minutes += seconds;

            if (slider.slider('option', 'max') < minutes ||
                slider.slider('option', 'min') > minutes) {
                return;
            }

            var workedTime = tc.time2Minutes(timeFieldsGroup['workEnd'].input.val());
            for (var v in timeFieldsGroup) {
                if (timeFieldsGroup.hasOwnProperty(v) && v != 'workEnd' && v != 'otherHours') {
                    var input = timeFieldsGroup[v].input;
                    workedTime -= doByTimeFormat(
                        input.val(),
                        function (time) {
                            return tc.time2Minutes(time);
                        },
                        function (time) {
                            return tc.timeWithComma2Minutes(time);
                        }
                    );
                }
            }

            workedTime = workedTime + time.val;

            if (workedTime < 0) {
                return;
            }

            workedHours.text(tc.minutes2TimeWithComma(workedTime));

            doByTimeFormat(
                field.input.val(), // just for time format check
                function (time) {
                    field.input.val(tc.minutes2Time(minutes));
                },
                function (time) {
                    field.input.val(tc.minutes2TimeWithComma(minutes));
                }
            );

            if (time.pos != undefined) {
                slider.slider('values', time.pos, minutes);
            } else {
                slider.slider('value', minutes);
            }
        }

        buttonAdd.on('click', function () {
            doAction($(this), STEP);
        });

        buttonSub.on('click', function () {
            doAction($(this), (STEP * (-1)));
        });

    });

}(window.jQuery, window.TimeConverter));