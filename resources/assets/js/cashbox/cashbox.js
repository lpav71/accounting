$('.confirm-btn').on('click', function () {
    let url = $(this).attr('data-url');
    let html = $(this).html();
    let $object = $(this);
    $(this).html('<div class="spinner-border"></div>');
    $.ajax({
            url: url,
            type: 'POST',
            success: (data) => {
                $object.parent().parent().css('background-color', 'green');
                $object.prop("disabled", true);
                $(this).html(html);
            },
            error: function () {
                alert('Something was wrong!');
                $(this).html(html);
            }
        }
    );
});