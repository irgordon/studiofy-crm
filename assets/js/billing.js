jQuery(document).ready(function($){
    // 1. Dynamic Rows
    let rowIdx = $('#billing-items-body tr').length;
    $('#add-item-row').click(function(){
        let html = `<tr>
            <td><input type="text" name="items[${rowIdx}][name]" class="widefat" placeholder="Item Name"></td>
            <td><input type="number" step="0.01" name="items[${rowIdx}][rate]" class="widefat item-rate" value="0.00"></td>
            <td><input type="number" name="items[${rowIdx}][qty]" class="widefat item-qty" value="1"></td>
            <td><span class="item-total">$0.00</span></td>
            <td><button type="button" class="button-link-delete remove-row">&times;</button></td>
        </tr>`;
        $('#billing-items-body').append(html);
        rowIdx++;
    });

    $(document).on('click', '.remove-row', function(){
        $(this).closest('tr').remove();
        calcTotals();
    });

    // 2. Options Logic
    $('#toggle-options').click(function(e){ e.stopPropagation(); $('#options-menu').toggle(); });
    $(document).click(function(){ $('#options-menu').hide(); });
    $('#options-menu').click(function(e){ e.stopPropagation(); });

    // Toggles
    $('#opt-deposit').change(function(){ $('#deposit-row').toggle($(this).is(':checked')); });
    $('#opt-discount').change(function(){ 
        $('#discount-row').toggle($(this).is(':checked')); 
        calcTotals(); 
    });
    
    // Fee / Tip Triggers
    $('input[name="tip_option"], #opt-service-fee, #discount_percent').change(function(){
        calcTotals();
    });

    // 3. Calculation Engine
    $(document).on('input', '.item-rate, .item-qty', function(){ calcTotals(); });

    function calcTotals() {
        let sub = 0;
        $('#billing-items-body tr').each(function(){
            let r = parseFloat($(this).find('.item-rate').val()) || 0;
            let q = parseFloat($(this).find('.item-qty').val()) || 0;
            let t = r * q;
            $(this).find('.item-total').text('$' + t.toFixed(2));
            sub += t;
        });

        // 1. Apply Discount
        let discPercent = $('#opt-discount').is(':checked') ? (parseFloat($('#discount_percent').val()) || 0) : 0;
        let discAmount = sub * (discPercent / 100);
        let taxableSub = Math.max(0, sub - discAmount);

        // 2. Apply Tax
        let tax = taxableSub * 0.06; // 6%

        // 3. Service Fee (3%)
        let svc = 0;
        if ($('#opt-service-fee').is(':checked')) {
            svc = taxableSub * 0.03;
            $('#service-fee-row').show();
        } else {
            $('#service-fee-row').hide();
        }

        // 4. Tip
        let tipPercent = parseFloat($('input[name="tip_option"]:checked').val()) || 0;
        let tip = taxableSub * (tipPercent / 100);
        if (tip > 0) {
            $('#tip-row').show();
            $('#input_tip_amount').val(tip.toFixed(2));
        } else {
            $('#tip-row').hide();
            $('#input_tip_amount').val(0);
        }

        // Updates
        $('#disp-subtotal').text(sub.toFixed(2));
        $('#disp-discount').text('-' + discAmount.toFixed(2));
        $('#disp-tax').text(tax.toFixed(2));
        $('#disp-service-fee').text(svc.toFixed(2));
        $('#disp-tip').text(tip.toFixed(2));
        
        let total = taxableSub + tax + svc + tip;
        $('#disp-total').text(total.toFixed(2));
    }

    // Init State
    if($('#opt-deposit').is(':checked')) $('#deposit-row').show();
    // Default Run
    calcTotals();
});
