jQuery(document).ready(function($){
    var frame;
    $('#studiofy-select-images').click(function(e){
        e.preventDefault();
        if(frame){ frame.open(); return; }
        frame = wp.media({ title: 'Select Images', button: { text: 'Use' }, multiple: true });
        frame.on('select', function(){
            var ids = frame.state().get('selection').map(function(a){ return a.id; });
            $('#studiofy_gallery_ids').val(ids.join(','));
        });
        frame.open();
    });
});
