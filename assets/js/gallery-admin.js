/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.1.9
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;
    let selectedFileId = 0;

    // 1. Folder Click
    $('.folder-item').click(function(e) {
        e.stopPropagation();
        $('.folder-item').removeClass('active');
        $(this).addClass('active');
        
        currentGalleryId = $(this).data('id');
        $('#current-folder-label').text($(this).text().trim());
        $('#upload-gallery-id').val(currentGalleryId);
        $('#btn-upload-media').prop('disabled', false);
        
        loadFiles(currentGalleryId);
        clearSelection();
    });

    // 2. Load Files
    function loadFiles(id) {
        $('#file-grid').html('<p style="padding:20px;">Loading...</p>');
        wp.apiFetch({ path: '/studiofy/v1/galleries/' + id + '/files' }).then(renderGrid);
    }

    function renderGrid(files) {
        const grid = $('#file-grid');
        grid.empty();
        
        if (files.length === 0) {
            grid.html('<div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>This folder is empty.</p></div>');
            return;
        }

        files.forEach(f => {
            const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
            const thumb = isImg ? f.file_url : '';
            
            const item = $(`
                <div class="studiofy-file-item" data-id="${f.id}" data-meta='${JSON.stringify(f)}'>
                    ${thumb ? `<img src="${thumb}" class="file-preview">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#eee; color:#555;">RAW</div>'}
                    <div class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></div>
                </div>
            `);
            
            // Select Logic
            item.click(function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                $(this).addClass('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            // Quick Delete Logic
            item.find('.file-trash-overlay').click(function(e) {
                e.stopPropagation();
                deleteFile(f.id, item);
            });
            
            grid.append(item);
        });
    }

    // 3. Metadata Sidebar Logic
    function showMeta(data) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        
        $('#meta-title').text(data.file_name);
        $('#meta-size').text(data.file_size);
        $('#meta-type').text(data.file_type);
        $('#meta-dims').text(data.dimensions || 'N/A');
        
        const isImg = ['jpg','jpeg','png','gif'].includes(data.file_type.toLowerCase());
        $('#meta-preview').html(isImg ? `<img src="${data.file_url}">` : '<div style="padding:20px; background:#eee; text-align:center;">No Preview</div>');
    }

    function clearSelection() {
        $('.studiofy-file-item').removeClass('selected');
        selectedFileId = 0;
        $('#meta-content').hide();
        $('#meta-empty').show();
    }

    // 4. Click Off to Deselect
    $('#file-grid').click(function(e) {
        if (!$(e.target).closest('.studiofy-file-item').length) {
            clearSelection();
        }
    });

    // 5. Delete Logic
    function deleteFile(id, domElement) {
        if(!confirm('Permanently delete this file?')) return;
        
        wp.apiFetch({
            path: '/studiofy/v1/galleries/files/' + id,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': studiofyGallerySettings.nonce }
        }).then(() => {
            domElement.remove();
            if(selectedFileId === id) clearSelection();
        });
    }

    // Sidebar Delete Button
    $('#btn-delete-file').click(function() {
        if(selectedFileId) {
            const el = $(`.studiofy-file-item[data-id="${selectedFileId}"]`);
            deleteFile(selectedFileId, el);
        }
    });

    // 6. Upload
    $('#btn-upload-media').click(function() { $('#file-input').click(); });
    $('#file-input').change(function() { if(this.files.length) $('#upload-form').submit(); });
});
