$(function () {
    $('[name="is_sending_external_data"]').on('change', function () {
        $('[data-toggle="is_sending_external_data"]').toggle(!this.checked);
    }).change();
});