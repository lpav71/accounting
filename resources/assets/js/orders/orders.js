$(function () {
    window.orderDetailPattern = $('[data-item="order-detail-pattern"]');
    let currentOrderDetail = 1;
    if (window.orderDetailPattern[0] !== undefined) {
        window.orderDetailPattern = window.orderDetailPattern[0].outerHTML.replace('data-item="order-detail-pattern"', 'data-item="order-detail"');
    }
    refresh();

    $('[data-action="order-detail-add"]').on('click', function () {
        $('[data-item="order-detail-button"]').before(orderDetailPattern.replace(/order_detail_add\[]/g, 'order_detail_add[' + currentOrderDetail + ']'));
        currentOrderDetail++;
        refresh();
    });
    $("#create-order #channel_id").change(function(){
        $("[data-item=order-detail-product]").trigger('change');
    })

    $('#phone').keydown(function(){
        if($(this).val()=='8'){
            $(this).val('7');
        }else if($(this).val()=='9'){
            $(this).val('79');
        }else if($(this).val()=='7'){
            $(this).val('7');
        }
    });

    function refresh() {
        $("#create-order [data-item='order-detail'] [data-item=order-detail-product]").change(function () {
            const $priceInput = $(this).parent().parent().parent().find('.product-price')
            axios.get($priceInput.data('product-price-url'), {
                params: {
                    product_id: $(this).val(),
                    channel_id: $('#channel_id').val()
                }
            }).then((response) => {
                let price = response.data.data.product.price;
                let price_discount = response.data.data.product.price_discount;
                if (price_discount == null || price < price_discount) {
                    $priceInput.val(response.data.data.product.price)
                } else {
                    $priceInput.val(response.data.data.product.price_discount)
                }
            })
        });
        $('.selectpicker-order-detail').selectpicker({
            liveSearch: true,
        });

        function checkCloseCertificate() {
            let closeTh = true;

            let table = document.getElementsByClassName('not-hidden');
            if(table.length > 0){
                let tds = table[0].getElementsByClassName('product-name');
                Array.from(tds).forEach(function (element) {
                    let select = element.getElementsByTagName('select');
                    if (select[0].options[select[0].selectedIndex].text.indexOf('Сертификат') +1) {
                        closeTh = false;
                    }
                });
            }    
            if(closeTh) {
                $('table th').filter('.certificate-number').css('display', 'none');
                $('.empty-td').css('display', 'none');
            }
        }

        $('[data-item="order-detail"][data-status="not-init"] [data-item="order-detail-product"]').change(function () {
            const orderDetail = $(this).parent().parent().parent();
            const orderDetailQuantity = orderDetail.find('[data-item="order-detail-quantity"]');
            const orderDetailStore = orderDetail.find('[data-item="order-detail-store"]');
            const orderDetailReference = orderDetail.find('[data-item="order-detail-reference"]');
            const productId = $(this).val();

            let orderDetailJsObject = orderDetail.get(0);
            let needSelect = orderDetailJsObject.querySelector(".product-name div select");

            if(needSelect.options[needSelect.selectedIndex].text.indexOf('Сертификат') + 1) {
                orderDetail.find('.certificate-number').css('display', 'table-cell');
                orderDetail.find('.empty-td').addClass('certificate-td').css('display', 'none');
                $('table th').filter('.certificate-number').css('display', 'table-cell');
                $('.empty-td:not(".certificate-td")').css('display', 'table-cell');
            } else {
                orderDetail.find('.certificate-number').css('display', 'none');
                orderDetail.find('.empty-td').removeClass('certificate-td');
                $('.empty-td:not(".certificate-td")').css('display', 'table-cell');

                checkCloseCertificate();
            }

            const storeId = orderDetailStore.val();
            orderDetailQuantity.addClass('badge-primary progress-bar-animated progress-bar-striped');
            orderDetailQuantity.html('');
            orderDetailReference.addClass('badge-primary progress-bar-animated progress-bar-striped');
            orderDetailReference.html('');
            $.ajax({
                url: "/stores/products/quantity",
                method: 'POST',
                data: {store_id: storeId, product_id: productId},
                success: function (data) {
                    orderDetailQuantity.removeClass('badge-primary progress-bar-animated progress-bar-striped');
                    orderDetailQuantity.html(data.quantity);
                    orderDetailReference.removeClass('badge-primary progress-bar-animated progress-bar-striped');
                    orderDetailReference.html(data.reference);
                }
            });
        });

        $('[data-item="order-detail"][data-status="not-init"] [data-item="order-detail-store"]').change(function () {
            const orderDetail = $(this).parent().parent().parent();
            const orderDetailQuantity = orderDetail.find('[data-item="order-detail-quantity"]');
            const orderDetailProduct = orderDetail.find('[data-item="order-detail-product"]');
            const orderDetailReference = orderDetail.find('[data-item="order-detail-reference"]');
            const productId = orderDetailProduct.val();
            const storeId = $(this).val();
            orderDetailQuantity.addClass('badge-primary progress-bar-animated progress-bar-striped');
            orderDetailQuantity.html('');
            $.ajax({
                url: "/stores/products/quantity",
                method: 'POST',
                data: {store_id: storeId, product_id: productId},
                success: function (data) {
                    orderDetailQuantity.removeClass('badge-primary progress-bar-animated progress-bar-striped');
                    orderDetailQuantity.html(data.quantity);
                    orderDetailReference.html(data.reference);
                }
            });
        });

        $('[data-item="order-detail"][data-status="not-init"]').removeAttr('data-status');

        $('[data-item="order-detail"]').each(function () {
            const productId = $(this).find('[data-item="order-detail-product"]')[0].value;
            const storeId = $(this).find('[data-item="order-detail-store"]')[0].value;
            const reference = $(this).find('[data-item="order-detail-reference"]')[0].value;
            let orderDetail = $(this);
            $.ajax({
                url: "/stores/products/quantity",
                method: 'POST',
                data: {store_id: storeId, product_id: productId},
                success: function (data) {
                    orderDetail.find('[data-item="order-detail-quantity"]').html(data.quantity);
                    orderDetail.find('[data-item="order-detail-reference"]').html(data.reference);
                }
            });
        });
        $('.selectpicker-order-detail-simple').selectpicker();
        $('[data-action="order-detail-delete"]')
            .off('click')
            .on('click', function () {
                this.parentElement.parentElement.remove();
                checkCloseCertificate();
            });
            $("[data-item=order-detail-product]").trigger('change');
    }

    $('#deliveryTime .time').timepicker({
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

    $('#deliveryDate .date').datepicker({
        'format': 'dd-mm-yyyy',
        'autoclose': true,
        'language': 'ru'
    });

    $('#deliveryTime').datepair();

    $('#searchOrders .date').datepicker({
        'format': 'dd-mm-yyyy',
        'autoclose': true,
        'language': 'ru'
    });

    let
        token = '66373a3074a76ecab4193f0adaf9b3bab996f46a',
        type = 'ADDRESS',
        postalCode = $('#postal_code'),
        city = $('#city'),
        address = $('#address'),
        carrier = $('#carrier_id');

    initSuggestions();
    calculateTariff();

    function initSuggestions() {

        city.suggestions({
            token: token,
            type: type,
            hint: false,
            bounds: 'region-area-city-settlement',
            onSelect: showPostalCode,
        });

        address.suggestions({
            token: token,
            type: type,
            hint: false,
            bounds: 'street-house-flat',
            constraints: city,
            onSelect: showPostalCode,
        });
    }

    function showPostalCode(suggestion) {
        postalCode.val(suggestion.data.postal_code);
        calculateTariff();
    }

    $('.selectpicker-ajax-pickup-points').selectpicker().ajaxSelectPicker({
        ajax: {
            data: function () {
                return {
                    q: '{{{q}}}',
                    postalCode: $('#postal_code').val(),
                    carrierId: $('#carrier_id').val(),
                };
            }
        },
        cache: false,
        minLength: 3,
        preserveSelectedPosition: 'before',
    }).change(function () {
        $('#pickup_point_name').val($(this).find('option:selected').attr('title'));
        $('#pickup_point_address').val($(this).find('option:selected').data('subtext'));
    });

    carrier.change(function () {
        if ($('#pickup_point_code').val() !== null && $('#pickup_point_code').val() !== "") {
            $('#pickupModal').modal('show');
        }
        $('#pickupModal [data-action="yes"]').click(function () {
            $('#pickup_point_name').val(null);
            $('#pickup_point_address').val(null);
            $('#pickup_point_code').find('option').remove();
            $('.selectpicker-ajax-pickup-points').selectpicker('refresh');
        });
    });

    postalCode.change(function () {
        calculateTariff();
    });

    function calculateTariff() {
        let CDEKerror = false;
        carrier.find('option').each(function () {
            const currentCarrier = $(this);
            if (!CDEKerror) {
                $.ajax({
                    type: "POST",
                    url: carrier.data('url'),
                    data: {
                        carrier_id: currentCarrier.attr('value'),
                        postal_code: postalCode.val(),
                        city: city.val()
                    },
                    success: function (data) {
                        if (data.value !== 0) {
                            currentCarrier.attr('data-subtext', data.value + ', ' + data.time);
                        } else {
                            currentCarrier.data('subtext', '');
                        }
                        carrier.selectpicker('refresh');
                    },
                    error: function () {
                        CDEKerror = true;
                    },
                    timeout: 50000
                });
            }
        });
    }

    // //Вывод инпута для номера сертификата при выборе его в select
    // $('.selectpicker-order-detail').change(function () {
    //     if($(this).val() == 10827) {
    //         $('.certificate-number').css('display', 'table-cell');
    //     } else {
    //         $('.certificate-number').css('display', 'none');
    //     }
    // });
});