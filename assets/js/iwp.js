function processIWPWCPayButton( response ){

    var iwp_paycode_submit = false;

    if ( iwp_paycode_submit ) {
        iwp_paycode_submit = false;
        return true;
    }

    var payCodeForm         = jQuery( 'form#payment-form, form#order_review' ),
        iwp_paycode_txnref  = payCodeForm.find( 'input.iwp_paycode_txnref' ),
        iwp_paycode_amount  = payCodeForm.find( 'input.iwp_paycode_amount' );

    iwp_paycode_amount.val( '' );

    payCodeForm.append( '<input type="hidden" class="iwp_paycode_txnref" name="iwp_paycode_txnref" value="' + response.txnref + '"/>' );
    payCodeForm.append( '<input type="hidden" class="iwp_paycode_amount" name="iwp_paycode_amount" value="' + response.amount + '"/>' );

    iwp_paycode_submit = true;

    payCodeForm.submit();

    jQuery( 'body' ).block({
        message: null,
        overlayCSS: {
            background: '#fff',
            opacity: 0.6
        },
        css: {
            cursor: "wait"
        }
    });

}
