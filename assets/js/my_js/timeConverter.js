/**
 * Created by aleš tichava on 3.8.2015.
 */

(function (global) {
    "use strict";

    var TimeConverter = {
        'timeRegExp': /^-?\d+:[0-5][0-9]$/,
        'hoursAndMinutesRegExp': /^\d+(,[05])?$/,

        /**
         * @param x
         * @returns {boolean}
         */
        'isInt': function (x) {
            var y = parseInt(x, 10);
            return !isNaN(y) && x == y && x.toString() == y.toString();
        },

        /**
         * @param time
         * @returns {boolean}
         */
        'isInTimeFormat': function (time) {
            return this.timeRegExp.test(time);
        },

        /**
         * @param time
         * @returns {boolean}
         */
        'isInTimeWithCommaFormat': function (time) {
            return this.hoursAndMinutesRegExp.test(time);
        },

        /**
         * @param {string} time
         * @returns {number} number of minutes
         */
        'time2Minutes': function (time) {
            if (!this.timeRegExp.test(time)) {
                throw 'Wrong format of argument "time".';
            }

            var timeParts = time.split(':');
            var hours = parseInt(timeParts[0], 10);
            var minutes = parseInt(timeParts[1], 10);

            return hours * 60 + minutes;
        },

        /**
         * @param {number} minutes
         * @returns {string} Time in format (-)HH:MM
         */
        'minutes2Time': function (minutes) {
            if (!this.isInt(minutes)) {
                throw 'Argument "minutes" must be integer number!';
            }

            var isNegative = minutes < 0;

            minutes = isNegative ? (minutes * (-1)) : minutes;
            var hours = Math.floor(minutes / 60);
            var mins = minutes - (hours * 60);

            if (mins < 10) {
                mins = '0' + mins;
            }

            return (isNegative ? '-' : '') + hours + ':' + mins;
        },

        /**
         * @param {string} hoursAndMinutes Time in format HH,MM (eg. 1,5| 1| but not 1,0)
         * @returns {number} number of minutes
         */
        'timeWithComma2Minutes': function (hoursAndMinutes) {
            if (!this.hoursAndMinutesRegExp.test(hoursAndMinutes)) {
                throw 'Wrong format of argument "hoursAndMinutes"';
            }

            if (this.isInt(hoursAndMinutes)) {
                hoursAndMinutes = hoursAndMinutes.toString();
            }


            var timeParts = hoursAndMinutes.split(',');
            var hours = parseInt(timeParts[0], 10) * 60;
            var minutes = parseInt(timeParts[1], 10);

            minutes = ((minutes == 5) ? 30 : 0);

            return (hours + minutes);
        },

        /**
         *
         * @param {number} minutes
         * @returns {string} Method returns time in format HH,MM where minutes are stepped by 30 mins
         */
        'minutes2TimeWithComma': function (minutes) {
            if (!this.isInt(minutes) || minutes < 0) {
                throw 'Argument "minutes" must be integer number!';
            }

            var mins = parseInt(minutes, 10);

            if (mins % 30 !== 0) {
                throw 'Argument "minutes" must be divisible by 30 without reminder!';
            }

            var t = this.minutes2Time(mins);
            var timeParts = t.split(':');

            var m;
            if (timeParts[1] == 30) {
                m = ',5';
            } else if (timeParts[1] == 0) {
                m = '';
            }

            return timeParts[0] + m;
        },

        'toMinutes': function (time) {
            if (this.isInTimeFormat(time)) {
                return this.time2Minutes(time);
            } else if (this.isInTimeWithCommaFormat(time)) {
                return this.timeWithComma2Minutes(time);
            } else {
                throw 'unexpected time format';
            }
        }
    };

    global.TimeConverter = global.tc = TimeConverter;

}(window));