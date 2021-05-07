$(function () {
    $('.time-picker').timepicker({
        'showDuration': true,
        'timeFormat': 'H:i',
        'lang': {mins: 'м.', hr: 'ч.', hrs: 'ч.'}
    });

    $.fn.datepicker.dates['ru'] = {
        days: ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота"],
        daysShort: ["Вос", "Пон", "Втр", "Срд", "Чет", "Пят", "Суб"],
        daysMin: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"],
        months: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
        monthsShort: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
        today: "Сегодня",
        clear: "Очистить",
        format: "dd.mm.yyyy",
        titleFormat: "MM yyyy",
        weekStart: 1
    };

    $('.date-picker').datepicker({
        'format': 'dd-mm-yyyy',
        'autoclose': true,
        'language': 'ru'
    });
});
