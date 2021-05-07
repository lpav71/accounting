$(function () {
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

    $('#analytics .date').datepicker({
        'format': 'dd-mm-yyyy',
        'autoclose': true,
        'language': 'ru'
    });

    $("#filterTable").on("keyup", function (e) {
        let value = $(this).val().toLowerCase();
        let operatorAnd = false;
        let values = value.split('||');

        if (value.indexOf('&&') > -1) {
            values = value.split("&&");
            operatorAnd = true;
        }

        let $table = $(e.currentTarget).closest('table');
        $table.find('tbody tr.js-row-data').filter(function () {
            let is_find = false;
            let is_find_for_and = true;
            for (let index = 0; index < values.length; ++index) {
                if ($(this).find("td:eq(0)").text().toLowerCase().indexOf(values[index].trim()) > -1) {
                    is_find = true;
                } else if (operatorAnd) {
                    is_find_for_and = false;
                }
            }


            if (is_find && is_find_for_and) {
                $(this).show();
            } else {
                $(this).hide();
            }

        });
        let data = [];
        data['total'] = [];
        $table.find('tbody').each(function () {
            let groupName = $(this).find('tr:first-child td').text();
            data[groupName] = [];
            data[groupName]['total'] = [];
            let $nonDisplays = $(this).find('tr.js-row-data:visible');
            $nonDisplays.each(function () {
                let subName = $(this).find('td:first-child').text().replace(/.*\//, '');
                $(this).find('td:not(:first-child)').each(function (index) {
                    if (typeof (data[groupName][subName]) === 'undefined') {
                        data[groupName][subName] = [];
                    }
                    value = parseFloat($(this).text());
                    if (value.toString() === $(this).text()) {
                        data[groupName][subName][index] = typeof (data[groupName][subName][index]) !== 'undefined' ? data[groupName][subName][index] + value : value;
                        data[groupName]['total'][index] = typeof (data[groupName]['total'][index]) !== 'undefined' ? data[groupName]['total'][index] + value : value;
                        data['total'][index] = typeof (data['total'][index]) !== 'undefined' ? data['total'][index] + value : value;
                    }
                    //Собираем для начала отдельно прибыль, себестоимость и расходы
                    if (typeof (data[groupName][subName][index]) === 'undefined') {
                        data[groupName][subName][index] = [];
                    }
                    if (typeof (data[groupName]['total'][index]) === 'undefined') {
                        data[groupName]['total'][index] = [];
                    }
                    if (typeof (data['total'][index]) === 'undefined') {
                        data['total'][index] = [];
                    }
                    if (typeof (data[groupName]['total'][index]['expenses']) === 'undefined') {
                        data[groupName]['total'][index]['expenses'] = [];
                    }
                    if (typeof (data[groupName][subName][index]['expenses']) === 'undefined') {
                        data[groupName][subName][index]['expenses'] = [];
                    }
                    if (typeof (data['total'][index]['expenses']) === 'undefined') {
                        data['total'][index]['expenses'] = [];
                    }
                    if(index === 15 && $(this).text().length) {
                        let profit = $(this).find('.analytics-profit').html();
                        if(profit !== undefined) {
                            data[groupName][subName][index]['profit'] = typeof (data[groupName][subName][index]['profit']) !== 'undefined' ? data[groupName][subName][index]['profit'] + parseFloat(profit) : parseFloat(profit);
                            data[groupName]['total'][index]['profit'] = typeof (data[groupName]['total'][index]['profit']) !== 'undefined' ? data[groupName]['total'][index]['profit'] + parseFloat(profit) : parseFloat(profit);
                            data['total'][index]['profit'] = typeof (data['total'][index]['profit']) !== 'undefined' ? data['total'][index]['profit'] + parseFloat(profit) : parseFloat(profit);
                        }
                    }
                    if(index === 16 && $(this).text().length) {
                        let expense = $(this).find('.analytics-expense');
                        if(expense.length > 0) {
                            if(expense.length > 1) {
                                let tmpExpense = 0;
                                expense.each(function (value) {
                                    tmpExpense += parseFloat(expense[value].innerHTML);
                                });
                                expense = tmpExpense;
                            } else {
                                expense = expense.html();
                            }
                            data[groupName][subName][index]['expense'] = typeof (data[groupName][subName][index]['expense']) !== 'undefined' ? data[groupName][subName][index]['expense'] + parseFloat(expense) : parseFloat(expense);
                            data[groupName]['total'][index]['expense'] = typeof (data[groupName]['total'][index]['expense']) !== 'undefined' ? data[groupName]['total'][index]['expense'] + parseFloat(expense) : parseFloat(expense);
                            data['total'][index]['expense'] = typeof (data['total'][index]['expense']) !== 'undefined' ? data['total'][index]['expense'] + parseFloat(expense) : parseFloat(expense);

                            $(this).find('.expenses').each(function (value, item) {
                                if(typeof data[groupName]['total'][index]['expenses'][$(item).find('.expense-name').html()] === 'undefined') {
                                    data[groupName]['total'][index]['expenses'][$(item).find('.expense-name').html()] = $(item).find('.analytics-expense').html();
                                } else {
                                    data[groupName]['total'][index]['expenses'][$(item).find('.expense-name').html()] = parseFloat(data[groupName]['total'][index]['expenses'][$(item).find('.expense-name').html()]) + parseFloat($(item).find('.analytics-expense').html());
                                }
                                if(typeof data[groupName][subName][index]['expenses'][$(item).find('.expense-name').html()] === 'undefined') {
                                    data[groupName][subName][index]['expenses'][$(item).find('.expense-name').html()] = $(item).find('.analytics-expense').html();
                                } else {
                                    data[groupName][subName][index]['expenses'][$(item).find('.expense-name').html()] = parseFloat(data[groupName][subName][index]['expenses'][$(item).find('.expense-name').html()]) + parseFloat($(item).find('.analytics-expense').html());
                                }
                                if(typeof data['total'][index]['expenses'][$(item).find('.expense-name').html()] === 'undefined') {
                                    data['total'][index]['expenses'][$(item).find('.expense-name').html()] = $(item).find('.analytics-expense').html();
                                } else {
                                    data['total'][index]['expenses'][$(item).find('.expense-name').html()] = parseFloat(data['total'][index]['expenses'][$(item).find('.expense-name').html()]) + parseFloat($(item).find('.analytics-expense').html());
                                }
                            });

                        }
                    }
                });
            });
            for (key in data) {
                if(data[key].length !== 16) {
                    for (key2 in data[key]) {
                        if(data[key][key2][16] !== undefined) {
                            // let self_value = typeof (data[key][key2][15]['self_value']) !== 'undefined' ? String(data[key][key2][15]['self_value'].toFixed(3)) : 0;
                            let expense = typeof (data[key][key2][16]['expense']) !== 'undefined' ? String(data[key][key2][16]['expense'].toFixed(3)) : 0;
                            let expenses = typeof (data[key][key2][16]['expenses']) !== 'undefined' ? data[key][key2][16]['expenses'] : '';
                            var divExpenses = document.createElement('div');
                            divExpenses.classList.add('d-flex');
                            divExpenses.innerHTML = '<span class="badge badge-secondary  ml-1">Сумма расходов : ' + expense + '</span>';
                            if(typeof (expenses) === 'object') {
                                let tmp = '';
                                for (key3 in expenses) {
                                    tmp += '<span class="badge badge-secondary ml-1">' + key3 + ' ' + expenses[key3] + '</span>';
                                }
                                divExpenses.innerHTML += '<span class="expenses d-flex">' + tmp + '</span>';
                            }
                            data[key][key2][16] = divExpenses;
                        }
                        if(data[key][key2][15] !== undefined) {
                            let profit = typeof (data[key][key2][15]['profit']) !== 'undefined' ? String(data[key][key2][15]['profit'].toFixed(3)) : 0;
                            var divProfit = document.createElement('div');
                            divProfit.innerHTML = '<span class="badge badge-secondary"> Прибыль : ' + profit + '</span>';
                            data[key][key2][15] = divProfit;
                        }
                    }
                }
            }
            $(this).find('tr.js-group-subtotal-data').each(function () {
                let groupSubName = $(this).find('td:first-child').text().replace(/.*\//, '');
                $(this).find('td:not(:first-child)').each(function (index) {
                    if (typeof (data[groupName][groupSubName]) === 'undefined' || typeof (data[groupName][groupSubName][index]) === 'undefined') {
                        $(this).text(0);
                    } else {
                        if (index === 5) { //TODO переписать на операторы из html
                            if (typeof (data[groupName][groupSubName][11]) !== 'undefined' && typeof (data[groupName][groupSubName][3]) !== 'undefined' && data[groupName][groupSubName][3] > 0) {
                                $(this).text(Math.round(data[groupName][groupSubName][11] / data[groupName][groupSubName][3] * 10000) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 6) { //TODO переписать на операторы из html
                            if (typeof (data[groupName][groupSubName][7]) !== 'undefined' && typeof (data[groupName][groupSubName][0]) !== 'undefined' && data[groupName][groupSubName][0] > 0) {
                                $(this).text(Math.round(data[groupName][groupSubName][7] / data[groupName][groupSubName][0] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 8) { //TODO переписать на операторы из html
                            if (typeof (data[groupName][groupSubName][7]) !== 'undefined' && typeof (data[groupName][groupSubName][11]) !== 'undefined' && data[groupName][groupSubName][11] > 0) {
                                $(this).text(Math.round(data[groupName][groupSubName][7] / data[groupName][groupSubName][11] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 9) { //TODO переписать на операторы из html
                            if (typeof (data[groupName][groupSubName][7]) !== 'undefined' && typeof (data[groupName][groupSubName][12]) !== 'undefined' && data[groupName][groupSubName][12] > 0) {
                                $(this).text(Math.round(data[groupName][groupSubName][7] / data[groupName][groupSubName][12] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 10) { //TODO переписать на операторы из html
                            if (typeof (data[groupName][groupSubName][7]) !== 'undefined' && typeof (data[groupName][groupSubName][13]) !== 'undefined' && data[groupName][groupSubName][13] > 0) {
                                $(this).text(Math.round(data[groupName][groupSubName][7] / data[groupName][groupSubName][13] * 100 * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 15) {
                            if (typeof (data[groupName][groupSubName][15]) !== 'undefined') {
                                $(this).html(data[groupName][groupSubName][15]);
                            }
                        } else if (index === 16) {
                            if (typeof (data[groupName][groupSubName][16]) !== 'undefined') {
                                $(this).html(data[groupName][groupSubName][16]);
                            }
                        } else {
                            $(this).text(Math.round(data[groupName][groupSubName][index] * 100) / 100);
                        }
                    }
                });
            });
            $(this).find('tr.js-group-total-data').each(function () {
                $(this).find('td:not(:first-child)').each(function (index) {
                    if (typeof (data[groupName]['total']) === 'undefined' || typeof (data[groupName]['total'][index]) === 'undefined') {
                        $(this).text(0);
                    } else {
                        if (index === 5) { //TODO переписать на операторы из html
                            if (typeof (data[groupName]['total'][11]) !== 'undefined' && typeof (data[groupName]['total'][3]) !== 'undefined' && data[groupName]['total'][3] > 0) {
                                $(this).text(Math.round(data[groupName]['total'][11] / data[groupName]['total'][3] * 10000) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 6) { //TODO переписать на операторы из html
                            if (typeof (data[groupName]['total'][7]) !== 'undefined' && typeof (data[groupName]['total'][0]) !== 'undefined' && data[groupName]['total'][0] > 0) {
                                $(this).text(Math.round(data[groupName]['total'][7] / data[groupName]['total'][0] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 8) { //TODO переписать на операторы из html
                            if (typeof (data[groupName]['total'][7]) !== 'undefined' && typeof (data[groupName]['total'][11]) !== 'undefined' && data[groupName]['total'][11] > 0) {
                                $(this).text(Math.round(data[groupName]['total'][7] / data[groupName]['total'][11] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 9) { //TODO переписать на операторы из html
                            if (typeof (data[groupName]['total'][7]) !== 'undefined' && typeof (data[groupName]['total'][12]) !== 'undefined' && data[groupName]['total'][12] > 0) {
                                $(this).text(Math.round(data[groupName]['total'][7] / data[groupName]['total'][12] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 10) { //TODO переписать на операторы из html
                            if (typeof (data[groupName]['total'][7]) !== 'undefined' && typeof (data[groupName]['total'][13]) !== 'undefined' && data[groupName]['total'][13] > 0) {
                                $(this).text(Math.round(data[groupName]['total'][7] / data[groupName]['total'][13] * 100 * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 15) {
                            if (typeof (data[groupName]['total'][15]) !== 'undefined') {
                                $(this).html(data[groupName]['total'][15]);
                            }
                        } else if(index === 16) {
                            if (typeof (data[groupName]['total'][16]) !== 'undefined') {
                                $(this).html(data[groupName]['total'][16]);
                            }
                        } else {
                            $(this).text(Math.round(data[groupName]['total'][index] * 100) / 100);
                        }
                    }
                });
            });
            $(this).find('tr.js-group-total-data-sum').each(function () {
                $(this).find('td:not(:first-child)').each(function (index) {
                    if ($(this).text() !== '') {
                        if (typeof (data[groupName]['total']) === 'undefined' || typeof (data[groupName]['total'][index]) === 'undefined') {
                            $(this).text(0);
                        } else {
                            $(this).text(Math.round(data[groupName]['total'][index] * 100) / 100);
                        }
                    }
                });
            });
            $(this).find('tr.js-total-data').each(function () {
                $(this).find('td:not(:first-child)').each(function (index) {
                    if (typeof (data['total']) === 'undefined' || typeof (data['total'][index]) === 'undefined') {
                        $(this).text(0);
                    } else {
                        if (index === 5) { //TODO переписать на операторы из html
                            if (typeof (data['total'][11]) !== 'undefined' && typeof (data['total'][3]) !== 'undefined' && data['total'][3] > 0) {
                                $(this).text(Math.round(data['total'][11] / data['total'][3] * 10000) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 6) { //TODO переписать на операторы из html
                            if (typeof (data['total'][7]) !== 'undefined' && typeof (data['total'][0]) !== 'undefined' && data['total'][0] > 0) {
                                $(this).text(Math.round(data['total'][7] / data['total'][0] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 8) { //TODO переписать на операторы из html
                            if (typeof (data['total'][7]) !== 'undefined' && typeof (data['total'][11]) !== 'undefined' && data['total'][11] > 0) {
                                $(this).text(Math.round(data['total'][7] / data['total'][11] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 9) { //TODO переписать на операторы из html
                            if (typeof (data['total'][7]) !== 'undefined' && typeof (data['total'][12]) !== 'undefined' && data['total'][12] > 0) {
                                $(this).text(Math.round(data['total'][7] / data['total'][12] * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 10) { //TODO переписать на операторы из html
                            if (typeof (data['total'][7]) !== 'undefined' && typeof (data['total'][13]) !== 'undefined' && data['total'][13] > 0) {
                                $(this).text(Math.round(data['total'][7] / data['total'][13] * 100 * 100) / 100);
                            } else {
                                $(this).text(0);
                            }
                        } else if (index === 15) {
                            if (typeof (data['total'][15]) !== 'undefined') {
                                let profit = typeof (data['total'][15]['profit']) !== 'undefined' ? String(data['total'][15]['profit'].toFixed(3)) : 0;
                                var divProfit = document.createElement('div');
                                divProfit.innerHTML = '<span class="badge badge-secondary"> Прибыль : ' + profit + '</span>';
                                $(this).html(divProfit);
                            }
                        } else if (index === 16) {
                            if (typeof (data['total'][16]) !== 'undefined') {
                                let expense = typeof (data['total'][16]['expense']) !== 'undefined' ? String(data['total'][16]['expense'].toFixed(3)) : 0;
                                let expenses = typeof (data['total'][16]['expenses']) !== 'undefined' ? data['total'][16]['expenses'] : '';
                                var divExpense = document.createElement('div');
                                divExpense.classList.add('d-flex');
                                divExpense.innerHTML = '<span class="badge badge-secondary  ml-1">Сумма расходов : ' + expense + '</span>';
                                if(typeof (expenses) === 'object') {
                                    let tmp = '';
                                    for (key3 in expenses) {
                                        tmp += '<span class="badge badge-secondary ml-1">' + key3 + ' ' + expenses[key3] + '</span>';
                                    }
                                    divExpense.innerHTML += '<span class="expenses d-flex">' + tmp + '</span>';
                                }
                                $(this).html(divExpense);
                            }
                        } else {
                            $(this).text(Math.round(data['total'][index] * 100) / 100);
                        }
                    }
                });
            });
            $(this).find('tr.js-total-data-sum').each(function () {
                $(this).find('td:not(:first-child)').each(function (index) {
                    if ($(this).text() !== '') {
                        if (typeof (data['total']) === 'undefined' || typeof (data['total'][index]) === 'undefined') {
                            $(this).text(0);
                        } else {
                            $(this).text(Math.round(data['total'][index] * 100) / 100);
                        }
                    }
                });
            });
        });
    });

    $('.js-chart').map(
        function (i, jsChart) {
            let name = $(jsChart).attr('id');
            if (Charts[name] !== undefined) {
                let options = Charts[name];
                let ctx = $(jsChart);
                new Chart(ctx, options);
            }
        }
    );

    let multiChart;

    function reloadMultiChart() {

        if (typeof Charts === "undefined") {
            return;
        }

        if (multiChart instanceof Chart) {
            multiChart.destroy();
        }

        let $multiChartFirst = $('#multiChartFirst');
        let $multiChartSecond = $('#multiChartSecond');

        let multiChartData = {
            type: 'line',
            data: {
                datasets: []
            },
            options: {
                scales: {
                    yAxes: []
                },
                aspectRatio: 2.5
            }
        };

        let multiChart1 = $multiChartFirst.val() + '-chart';

        if (typeof Charts[multiChart1] !== "undefined") {

            multiChartData['data']['labels'] = Charts[multiChart1]['data']['labels'];
            let colors = [
                "#FF0000",
                "#00FF00",
                "#0000FF",
                "#FFFF00",
                "#FF00FF",
                "#00FFFF",
                "#000000",
                "#800000",
                "#008000",
                "#000080",
                "#808000",
                "#800080",
                "#008080",
                "#808080",
                "#C00000",
                "#00C000",
                "#0000C0",
                "#C0C000",
                "#C000C0",
                "#00C0C0",
                "#C0C0C0",
                "#400000",
                "#004000",
                "#000040",
                "#404000",
                "#400040",
                "#004040",
                "#404040",
                "#200000",
                "#002000",
                "#000020",
                "#202000",
                "#200020",
                "#002020",
                "#202020",
                "#600000",
                "#006000",
                "#000060",
                "#606000",
                "#600060",
                "#006060",
                "#606060",
                "#A00000",
                "#00A000",
                "#0000A0",
                "#A0A000",
                "#A000A0",
                "#00A0A0",
                "#A0A0A0",
                "#E00000",
                "#00E000",
                "#0000E0",
                "#E0E000",
                "#E000E0",
                "#00E0E0",
                "#E0E0E0",
            ];
            Charts[multiChart1]['data']['datasets'].map(
                function (dataset) {
                    let color = colors.shift();
                    multiChartData['data']['datasets'].push(
                        {
                            backgroundColor: color,
                            borderColor: color,
                            data: dataset['data'],
                            fill: false,
                            label: Charts[multiChart1]['options']['title']['text'] + ':' + dataset['label'],
                            hidden: true,
                            yAxisID: 'y-axis-1'
                        }
                    );
                }
            );

            multiChartData['options']['scales']['yAxes'][0] = {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    beginAtZero: true
                },
                id: 'y-axis-1'
            };

            let multiChart2 = $multiChartSecond.val() + '-chart';

            if (Charts[multiChart2] !== undefined) {

                let colors = [
                    "#FF0000",
                    "#00FF00",
                    "#0000FF",
                    "#FFFF00",
                    "#FF00FF",
                    "#00FFFF",
                    "#000000",
                    "#800000",
                    "#008000",
                    "#000080",
                    "#808000",
                    "#800080",
                    "#008080",
                    "#808080",
                    "#C00000",
                    "#00C000",
                    "#0000C0",
                    "#C0C000",
                    "#C000C0",
                    "#00C0C0",
                    "#C0C0C0",
                    "#400000",
                    "#004000",
                    "#000040",
                    "#404000",
                    "#400040",
                    "#004040",
                    "#404040",
                    "#200000",
                    "#002000",
                    "#000020",
                    "#202000",
                    "#200020",
                    "#002020",
                    "#202020",
                    "#600000",
                    "#006000",
                    "#000060",
                    "#606000",
                    "#600060",
                    "#006060",
                    "#606060",
                    "#A00000",
                    "#00A000",
                    "#0000A0",
                    "#A0A000",
                    "#A000A0",
                    "#00A0A0",
                    "#A0A0A0",
                    "#E00000",
                    "#00E000",
                    "#0000E0",
                    "#E0E000",
                    "#E000E0",
                    "#00E0E0",
                    "#E0E0E0",
                ];
                Charts[multiChart2]['data']['datasets'].map(
                    function (dataset) {
                        let color = colors.shift();
                        multiChartData['data']['datasets'].push(
                            {
                                backgroundColor: hexToRgbA(color, '0.5'),
                                borderColor: hexToRgbA(color, '0.5'),
                                data: dataset['data'],
                                fill: false,
                                label: Charts[multiChart2]['options']['title']['text'] + ':' + dataset['label'],
                                hidden: true,
                                yAxisID: 'y-axis-2'
                            }
                        );
                    }
                );

                multiChartData['options']['scales']['yAxes'][1] = {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    ticks: {
                        beginAtZero: true
                    },
                    id: 'y-axis-2',
                    gridLines: {
                        drawOnChartArea: false
                    }
                };
            }
        }



        if (multiChartData['data']['datasets'].length < 1) {
            multiChartData = {};
        }

        let ctx= $('#multiChart-chart');
        multiChart = new Chart(ctx, multiChartData);
        ctx.focus();

    }

    function hexToRgbA(hex, alpha = '1'){
        let c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c= hex.substring(1).split('');
            if(c.length === 3){
                c= [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c= '0x'+c.join('');
            return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+',' + alpha + ')';
        }
        throw new Error('Bad Hex');
    }

    $('.js-multiChart').change(reloadMultiChart);

    reloadMultiChart();

    $('#chartSelect').on('change', function (e) {
        $('.tab-pane').removeClass('active').removeClass('show');
        $('#' + $(this).val() + '.tab-pane').addClass('active').addClass('show');
    });

    $('#subReportSelect').on('change', function (e) {
        $('.tab-pane').removeClass('active').removeClass('show');
        $('#' + $(this).val() + '.tab-pane').addClass('active').addClass('show');
    });
});