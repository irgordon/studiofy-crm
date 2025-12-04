jQuery(document).ready(function($) {
    // Sortable
    $('#studiofy-clause-container').sortable({ 
        handle: '.clause-title', // Drag by title bar
        update: serializeClauses 
    });

    // Add Clause
    $('#add-clause').click(function() {
        var template = `
            <div class="clause-row" style="background:#f9f9f9; border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:4px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <input type="text" class="clause-title widefat" style="font-weight:bold; width:80%;" placeholder="Clause Title (e.g. Cancellation)">
                    <span class="dashicons dashicons-trash remove-clause" style="cursor:pointer; color:#d63638;"></span>
                </div>
                <textarea class="clause-body widefat" rows="5" placeholder="Enter terms..."></textarea>
            </div>`;
        $('#studiofy-clause-container').append(template);
    });

    // Remove Clause
    $(document).on('click', '.remove-clause', function() {
        if(confirm('Delete this clause?')) {
            $(this).closest('.clause-row').remove();
            serializeClauses();
        }
    });

    // Serialize on Change
    $(document).on('keyup change', '.clause-title, .clause-body', serializeClauses);

    function serializeClauses() {
        var data = [];
        $('.clause-row').each(function() {
            var title = $(this).find('.clause-title').val();
            var body = $(this).find('.clause-body').val();
            if(title || body) data.push({ title: title, body: body });
        });
        $('#studiofy_clauses_input').val(JSON.stringify(data));
    }
});
