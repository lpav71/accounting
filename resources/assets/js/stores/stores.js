$(function () {
    $('.store-transfer-submit-button').click(function(){
        $(this).prop('disabled', true);
        $('form').submit();
    })
});