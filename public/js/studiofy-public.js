jQuery(document).ready(function($) {
    var canvas = document.getElementById('signature-pad');
    if(!canvas) return;
    
    var pad = new SignaturePad(canvas);
    
    $('#save-sig').click(function(e) {
        e.preventDefault();
        if(pad.isEmpty()) return alert('Please sign first.');
        
        $.post(studiofy_vars.ajax_url, {
            action: 'studiofy_submit_signature',
            security: studiofy_vars.nonce,
            id: $('#cid').val(),
            token: $('#ctoken').val(),
            signature: pad.toDataURL()
        }, function(res) {
            if(res.success) location.reload();
            else alert('Error saving.');
        });
    });
});
