/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.1.8
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;

    // 1. Folder Click
    $('.folder-item').click(function() {
        $('.folder-item').removeClass('active');
        $(this).addClass('active');
        currentGalleryId = $(this).data('id');
        $('#current-folder-label').text($(this).text().trim());
        $('#upload-gallery-id').val(currentGalleryId);
        
        loadFiles(currentGalleryId);
        $('#meta-sidebar').removeClass('open');
    });

    // 2. Load Files AJAX
    function loadFiles(id) {
        $('#file-grid').html('<p style="padding:20px;">Loading...</p>');
        
        wp.apiFetch({
            path: '/studiofy/v1/galleries/' + id + '/files',
            method: 'GET'
        }).then(files => {
            renderGrid(files);
        }).catch(err => {
            $('#file-grid').html('<p style="color:red; padding:20px;">Error loading files.</p>');
        });
    }

    // 3. Render Grid
    function renderGrid(files) {
        const grid = $('#file-grid');
        grid.empty();
        
        if (files.length === 0) {
            grid.html('<p style="padding:20px; color:#888;">This folder is empty.</p>');
            return;
        }

        files.forEach(f => {
            let thumb = f.file_url;
            // Placeholders for RAW
            if(!['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase())) {
                thumb = ''; // Or use a generic icon path
            }

            const item = $(`
                <div class="studiofy-file-item" data-meta='${JSON.stringify(f)}'>
                    ${thumb ? `<img src="${thumb}" class="file-preview">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#ddd;">RAW</div>'}
                    <span class="file-type-badge">${f.file_type}</span>
                </div>
            `);
            
            item.click(function() {
                $('.studiofy-file-item').removeClass('selected');
                $(this).addClass('selected');
                showMeta(f);
            });
            
            grid.append(item);
        });
    }

    // 4. Show Metadata Sidebar
    function showMeta(data) {
        $('#meta-sidebar').addClass('open');
        $('#meta-title').text(data.file_name);
        $('#meta-date').text(data.created_at);
        $('#meta-type').text(data.file_type.toUpperCase());
        $('#meta-dims').text(data.dimensions || 'N/A');
        $('#meta-size').text(data.file_size || 'N/A');
        $('#meta-author').text(data.photographer || 'Unknown');
        $('#meta-project').text(data.project_name || 'Unassigned');
        
        let previewHtml = '';
        if(['jpg','jpeg','png','gif'].includes(data.file_type.toLowerCase())) {
            previewHtml = `<img src="${data.file_url}">`;
        }
        $('#meta-preview').html(previewHtml);
    }

    $('.close-meta').click(function(){ $('#meta-sidebar').removeClass('open'); });

    // 5. Upload Handling with Validation
    $('#btn-upload-media').click(function() {
        if(currentGalleryId === 0) {
            alert('Please select a folder first.');
            return;
        }
        $('#file-input').click();
    });

    $('#file-input').change(function() {
        const files = this.files;
        const maxBytes = parseInt(studiofyGallerySettings.max_upload_size);
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxBytes) {
                alert(`File "${files[i].name}" is too large. Max size is ${Math.round(maxBytes/1024/1024)}MB.`);
                $(this).val(''); // Clear input
                return;
            }
        }
        
        if (files.length > 0) {
            // Auto submit form on selection
            $('#upload-form').submit();
        }
    });
});
