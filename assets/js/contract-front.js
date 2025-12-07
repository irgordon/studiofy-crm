jQuery(document).ready(function($) {
    if (!document.getElementById('signature-canvas')) return;

    var canvas = document.getElementById('signature-canvas');
    var signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)'
    });

    // Resize canvas correctly
    function resizeCanvas() {
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear();
    }
    // window.addEventListener("resize", resizeCanvas);
    // resizeCanvas(); // Initial resize

    $('#clear-signature').click(function() {
        signaturePad.clear();
    });

    $('#studiofy-sign-form').on('submit', function(e) {
        e.preventDefault();
        
        if (signaturePad.isEmpty()) {
            alert('Please provide a signature.');
            return;
        }

        var btn = $('#btn-submit-sign');
        btn.text('Signing...').prop('disabled', true);

        var sigData = signaturePad.toDataURL();
        var formData = {
            action: 'studiofy_client_sign',
            nonce: studiofyContractSettings.nonce,
            contract_id: $('input[name="contract_id"]').val(),
            signed_name: $('#signed_name').val(),
            signature_data: sigData
        };

        $.post(studiofyContractSettings.ajax_url, formData, function(response) {
            if (response.success) {
                alert('Contract signed successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
                btn.text('Submit Signature').prop('disabled', false);
            }
        });
    });
});
