jQuery(document).ready(function($) {
    var canvas = document.getElementById('signature-pad');
    if(!canvas) return;
    
    // Adjust canvas for retina displays
    function resizeCanvas() {
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
    }
    window.onresize = resizeCanvas;
    resizeCanvas();

    var pad = new SignaturePad(canvas);
    
    $('#save-sig').click(function(e) {
        e.preventDefault();
        if(pad.isEmpty()) return alert('Please sign first.');
        
        var btn = $(this);
        btn.text('Signing...').prop('disabled', true);

        $.post(studiofy_vars.ajax_url, {
            action: 'studiofy_submit_signature',
            security: studiofy_vars.nonce,
            id: $('#cid').val(),
            token: $('#ctoken').val(),
            signature: pad.toDataURL()
        }, function(res) {
            if(res.success) {
                alert('Contract Signed!');
                location.reload();
            } else {
                alert('Error: ' + res.data);
                btn.text('Agree & Sign').prop('disabled', false);
            }
        });
    });
});
