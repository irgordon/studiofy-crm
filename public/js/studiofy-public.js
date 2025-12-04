(function($){
    $(function(){
        var canvas = document.getElementById('signature-pad');
        if(!canvas) return;
        var pad = new SignaturePad(canvas);
        $('#save-sig').click(function(e){
            e.preventDefault();
            if(pad.isEmpty()) return alert('Sign first');
            $.post(studiofy_vars.ajax_url, {
                action: 'studiofy_submit_signature',
                security: studiofy_vars.nonce,
                id: studiofy_vars.post_id,
                signature: pad.toDataURL()
            }, function(res){
                if(res.success) location.reload();
            });
        });
    });
})(jQuery);
