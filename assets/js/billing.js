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

    // 2. Remove Row
    $(document).on('click', '.remove-row', function(){
        $(this).closest('tr').remove();
        calcTotals();
    });

    // 3. Calc Logic
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

        let tax = sub * 0.06; // 6% Tax hardcoded for demo, normally dynamic
        $('#disp-subtotal').text(sub.toFixed(2));
        $('#disp-tax').text(tax.toFixed(2));
        $('#disp-total').text((sub + tax).toFixed(2));
    }

    // 4. Options Menu Toggle
    $('#toggle-options').click(function(e){
        e.stopPropagation();
        $('#options-menu').toggle();
    });
    
    // Close dropdown on click outside
    $(document).click(function(){ $('#options-menu').hide(); });
    $('#options-menu').click(function(e){ e.stopPropagation(); });

    // 5. Deposit Logic
    $('#opt-deposit').change(function(){
        if($(this).is(':checked')) $('#deposit-row').slideDown();
        else $('#deposit-row').slideUp();
    });
    // Trigger on load
    if($('#opt-deposit').is(':checked')) $('#deposit-row').show();

    // Initial Calc
    calcTotals();
});
