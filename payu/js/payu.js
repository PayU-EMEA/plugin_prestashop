var openpayu = openpayu || {};
openpayu.options = openpayu.options || {};

$(document).ready(function () {

    $('.payMethodEnable .payMethodLabel').click(function () {
        $('.payMethod').removeClass('payMethodActive');
        $(this).closest('.payMethod').addClass('payMethodActive');
        $(this).prev().prop('checked', true);
    });

    $('#HOOK_PAYMENT').on('click', 'a.payu', function () {
        return doubleClickPrevent(this);
    });

    $('#payuForm').submit(function () {
        return doubleClickPrevent(this);
    })

    $('#payuRetryPayment17').insertBefore($('#order-history'));

    $("img[src*='raty_small.png']")
        .parent()
        .append("<span>Rata już od: <span id='payu-installments-mini-cart'></span></spaan><script type='text/javascript'>openpayu.options.creditAmount=prestashop.cart.totals.total.amount;OpenPayU.Installments.miniInstallment('#payu-installments-mini-cart');</script>");
});

function doubleClickPrevent(object) {
    if ($(object).data('clicked')) {
        return false;
    }
    $(object).data('clicked', true);
    return true;
}
