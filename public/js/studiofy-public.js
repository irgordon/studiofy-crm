(function($){
    $(function(){
        var c = document.getElementById('signature-pad');
        if(!c) return;
        var p = new SignaturePad(c);
        $('#save-sig').click(function(e){
            e.preventDefault();
            if(p.isEmpty()) return alert('Sign');
            $.post(studiofy_vars.ajax_url, {
                action: 'studiofy_submit_signature',
                security: studiofy_vars.nonce,
                id: studiofy_vars.post_id,
                signature: p.toDataURL()
            }, function(r){ if(r.success) location.reload(); });
        });
    });
})(jQuery);
