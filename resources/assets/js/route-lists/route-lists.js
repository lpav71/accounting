$(function () {
    $('[data-action="order-detail-state-ajax"]').click(ajaxOrderDetailState);

    function ajaxOrderDetailState() {
        let $orderDetailBLock = $(this).closest('[data-container="order-detail"]');
        let $stateBlock = $orderDetailBLock.find('[data-container="order-detail-state"]');
        let $stateMenuBlock = $orderDetailBLock.find('[data-container="order-detail-state-menu"]');

        $orderDetailBLock.find('[data-action="order-detail-state-ajax"]').attr('disabled', true);
        $.ajax({
            url: $(this).data('url'),
            method: 'POST',
            success: function (data) {
                if (data.okState !== undefined) {
                    $stateBlock.html(data.okState);
                    if (data.nextStates !== undefined) {
                        $stateMenuBlock.replaceWith($(data.nextStates));
                        $orderDetailBLock.find('[data-action="order-detail-state-ajax"]').click(ajaxOrderDetailState);
                    }
                } else {
                    $orderDetailBLock.find('[data-action="order-detail-state-ajax"]').attr('disabled', false);
                }
            }
        });
    }

    ajaxRouteStateInit();

    function ajaxRouteStateInit($container = $(document)) {
        let $select = $container.find('[data-action="ajax-route-state-select"]');

        $(document).find('.modal').hide();
        $(document).find('.modal-backdrop').hide();
        $('body').removeClass('modal-open');

        $select.each(function () {
            if ($(this).data('value') === undefined) {
                $(this).data('value', $(this).val());
            } else {
                $(this).off('change');
                $(this).val($(this).data('value'));
            }
        });

        $select.change(ajaxStateSelect);
    }

    function ajaxStateSelect() {
        let $select = $(this);
        let $container = $select.closest('[data-container="' + $(this).data('select-container') + '"]');
        let modal = $select.find('option:selected').data('modal');

        if (modal !== undefined) {
            let $modal = $('#' + modal);
            let $modalSubmit = $modal.find('[data-action="submit"]');

            $modal.modal('show');
            $modal.on('hidden.bs.modal', function () {
                $modalSubmit.off('click');
                ajaxRouteStateInit($container);
            });

            $modalSubmit.click(function () {

                let data = {};

                $modal.find('input, textarea, select').each(function () {
                    data[this.name] = $(this).val();
                });
                ajaxStateSelectSend($select, $container, data);
            });
        } else {
            ajaxStateSelectSend($select, $container);
        }

    }

    function ajaxStateSelectSend($select, $container, data = {}) {
        let html = $container.html();
        let store = $('#store_id').val();
        $container.html('<div class="spinner-border"></div>');

        $.ajax({
            url: $select.data('url').replace('_state_', $select.val()).replace('_store_', store),
            method: 'POST',
            data: data,
            success: function (data) {
                if (data.okState !== undefined) {
                    if (data.html !== undefined) {
                        let $html = $(data.html);
                        ajaxRouteStateInit($html);
                        $container.replaceWith($html);
                    }
                } else {
                    $container.html(html);
                    ajaxRouteStateInit($container);
                }
            },
            error: function (data) {
                $container.html(html);
                ajaxRouteStateInit($container);
                alert(data.responseJSON.errors);
            }
        });
    }

    $('[data-js-group="checked-unchecked"]').each(
        function () {
            let $group = $(this);

            if (!$group.find('input[type="checkbox"]').is(':checked')) {
                $group.find('select').attr('disabled', true);
            }

            $group.find('input[type="checkbox"]').change(function () {
                if ($(this).is(':checked')) {
                    $group.find('select').removeAttr('disabled');
                } else {
                    $group.find('select').attr('disabled', true);
                }

            });
        }
    );

    //Событие переключения на новый экшн оплаты
    $('#btn-pay').on('click', function () {
        var ids = '';
        let i = 0;
        $(".pay-order:checkbox:checked").each(function () {
           ids += '&points[' + i + ']=' + $(this).val();
           i++;
        });
        ids += '&courier=' + $('#courier_id').val();

        if (ids.length) {
            window.location = '/route-lists/pay?' + ids;
        }
    });

    var tmpPointers = [];

    //Отправка запроса в спутник
    $('#sputnik-search').on('click', function () {
        var address = $('#city').val().trim();
        var x = window.location;
        address = address.replace(/ /g, '+');
        $.ajax({
            url: x.origin + '/route-list-manage/search/' + address,
            method: 'GET',
            success: function (data) {
                if (tmpPointers.length) {
                    tmpPointers.forEach(function (item, i, arr) {
                        globalMap.geoObjects.remove(item);
                    });
                }
                data.result.address.forEach(function (item, i, arr) {
                    item.features.forEach(function (item, i, arr) {
                        var str = '';
                        for (let j = item.properties.address_components.length - 1; j >= 0; j--) {
                            str += item.properties.address_components[j].value + ', ';
                        }
                        str = str.substring(0, str.length - 2);
                        var placeMark = new ymaps.Placemark([item.geometry.geometries[0].coordinates[1], item.geometry.geometries[0].coordinates[0]], {balloonContent: str});
                        tmpPointers.push(placeMark);
                        globalMap.geoObjects.add(placeMark);
                    });
                });
            },
            error: function (data) {
                alert('Что-то пошло не так.');
            }
        });
    });
});