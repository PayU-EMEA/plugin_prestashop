var openpayu = openpayu || {};
openpayu.options = openpayu.options || {};

$(document).ready(function () {
    $('.payMethodEnable .payMethodLabel').click(function () {
        $('.payMethod').removeClass('payMethodActive');
        $(this).closest('.payMethod').addClass('payMethodActive');
        $(this).prev().prop('checked', true);
        if ($(this).data('autosubmit')) {
            $('#payuForm').submit();
        }
    });

    $('#HOOK_PAYMENT').on('click', 'a.payu', function () {
        return doubleClickPrevent(this);
    });

    $('#payuForm').submit(function () {
        return doubleClickPrevent(this);
    });

    $('#payuRetryPayment17').insertBefore($('#order-history'));

    if (window.payuPaymentLoaded) {
        groupPayuMethod();
    }

    $('.payu-read-more').on('click', function () {
        $(this).hide();
        var elementToShow = $(this).data('more');
        $('#' + elementToShow).show();
    });
});

function doubleClickPrevent(object) {
    if ($(object).data('clicked')) {
        return false;
    }
    $(object).data('clicked', true);
    return true;
}

function groupPayuMethod() {
    var payuIndexes = [];
    for (var i = 0; i < 20; ++i) {
        var isFound = $("#payment-option-" + i + "-additional-information .payu-marker-class").length > 0;
        if (isFound) {
            payuIndexes.push(i);
        }
    }
    if (payuIndexes.length > 0) {
        $(".payment-options").append("<fieldset id='payu-methods-grouped' class='payu-payment-fieldset-1-7'>" +
            "   <legend class='payu-payment-legend-1-7'>" +
            "        <span class='logo' />" +
            "    </legend>" +
            "</fieldset>");
    }
    for (var indexOfPayuElement in payuIndexes) {
        var element1 = $("#payment-option-" + payuIndexes[indexOfPayuElement] + "-container").parent();
        var element2 = $("#payment-option-" + payuIndexes[indexOfPayuElement] + "-additional-information");
        var element3 = $("#pay-with-payment-option-" + payuIndexes[indexOfPayuElement] + "-form");
        element1.detach().appendTo('#payu-methods-grouped');
        element2.detach().appendTo('#payu-methods-grouped');
        element3.detach().appendTo('#payu-methods-grouped');
    }
}
