$(function () {
    $('.editable').editable().on('hidden', function (e, reason) {
        let locale = $(this).data('locale');
        if (reason === 'save') {
            $(this).removeClass('status-0').addClass('status-1');
        }
        if (reason === 'save' || reason === 'nochange') {
            let $next = $(this).closest('tr').next().find('.editable.locale-' + locale);
            setTimeout(function () {
                $next.editable('show');
            }, 300);
        }
    });

    $('.group-select').on('change', function () {
        let group = $(this).val();
        if (group) {
            window.location.href = $(this).attr('data-view') + '/' + $(this).val();
        } else {
            window.location.href = $(this).attr('data-index');
        }
    });

    $("a.delete-key").click(function (event) {
        event.preventDefault();
        let row = $(this).closest('tr');
        let url = $(this).attr('href');
        let id = row.attr('id');
        $.post(url, {id: id}, function () {
            row.remove();
        });
    });

    $('.form-import').on('ajax:success', function (e, data) {
        $('div.success-import strong.counter').text(data.counter);
        $('div.success-import').slideDown();
        window.location.reload();
    });

    $('.form-find').on('ajax:success', function (e, data) {
        $('div.success-find strong.counter').text(data.counter);
        $('div.success-find').slideDown();
        window.location.reload();
    });

    $('.form-publish').on('ajax:success', function (e, data) {
        $('div.success-publish').slideDown();
    });

    $('.form-publish-all').on('ajax:success', function (e, data) {
        $('div.success-publish-all').slideDown();
    });
});