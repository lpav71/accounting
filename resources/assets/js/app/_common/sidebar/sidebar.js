$(function () {

    $('#sidebar-menu').metisMenu({
        activeClass: 'open'
    });


    $('#sidebar-collapse-btn').on('click', function (event) {
        event.preventDefault();

        $("#app").toggleClass("sidebar-open");
    });

    $("#sidebar-overlay").on('click', function () {
        $("#app").removeClass("sidebar-open");
    });

    let appContainer = $('#app');
    let mobileHandle = $('#sidebar-mobile-menu-handle');

    mobileHandle.swipe({
        swipeLeft: function () {
            if (appContainer.hasClass("sidebar-open")) {
                appContainer.removeClass("sidebar-open");
            }
        },
        swipeRight: function () {
            if (!appContainer.hasClass("sidebar-open")) {
                appContainer.addClass("sidebar-open");
            }
        },
        // excludedElements: "button, input, select, textarea, .noSwipe, table",
        triggerOnTouchEnd: false
    });

});