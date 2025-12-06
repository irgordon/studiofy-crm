/**
 * Studiofy Gallery Admin JS
 * @package Studiofy
 * @version 2.1.11
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;
    let selectedFileId = 0;

    // 1. Folder Click
    $('.folder-item').click(function(e) {
        $('.folder-item').removeClass('active');
        $(this).addClass('active');
        currentGalleryId = $(this).data('id');
        $('#current-folder-label').text($(this).text().trim());
        $('#upload-gallery-id').val(currentGalleryId);
        $('#btn-upload-media').prop('disabled', false);
        loadFiles(currentGalleryId);
        clearSelection();
    });

    // 2. Load
    function loadFiles(id) {
        $('#file-grid').html('<p style="padding:20px;">Loading...</p>');
        wp.apiFetch({ path: '/studiofy/v1/galleries/' + id + '/files' }).then(renderGrid);
    }

    // 3. Render
    function renderGrid(files) {
        const grid = $('#file-grid');
        grid.empty();
        if (files.length === 0) {
            grid.html('<div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>Empty folder.</p></div>');
            return;
        }

        files.forEach(f => {
            const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
            const thumb = isImg ? f.file_url : '';
            
            const item = $(`
                <div class="studiofy-file-item" data-id="${f.id}">
                    <span class="file-type-overlay">${f.file_type}</span>
                    ${thumb ? `<img src="${thumb}" class="file-preview">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#eee;">RAW</div>'}
                    <div class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></div>
                </div>
            `);
            
            // Click to Select
            item.click(function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                $(this).addClass('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            // Double Click to View Large
            item.dblclick(function(e) {
                if(isImg) openLightbox(f.file_url);
            });

            // Delete
            item.find('.file-trash-overlay').click(function(e) {
                e.stopPropagation();
                if(confirm('Delete file?')) {
                    wp.apiFetch({ path: '/studiofy/v1/galleries/files/' + f.id, method: 'DELETE' }).then(() => item.remove());
                }
            });
            
            grid.append(item);
        });
    }

    // 4. Meta Sidebar
    function showMeta(f) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        
        // Populate
        $('#inp-meta-title').val(f.meta_title || f.file_name);
        $('#inp-meta-author').val(f.meta_photographer || '');
        $('#inp-meta-project').val(f.meta_project || '');
        $('#meta-size').text(f.file_size);
        $('#meta-type').text(f.file_type);
        $('#meta-dims').text(f.dimensions || '-');
        
        const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
        $('#meta-preview').html(isImg ? `<img src="${f.file_url}">` : '');
        
        // Save Handler
        $('#btn-save-meta').off('click').click(function() {
            wp.apiFetch({
                path: '/studiofy/v1/galleries/files/' + f.id,
                method: 'POST',
                data: {
                    meta_title: $('#inp-meta-title').val(),
                    meta_photographer: $('#inp-meta-author').val(),
                    meta_project: $('#inp-meta-project').val()
                }
            }).then(() => alert('Metadata Saved'));
        });

        // View Large Button
        $('#btn-view-large').off('click').click(function() {
            if(isImg) openLightbox(f.file_url);
        });
    }

    function clearSelection() {
        $('.studiofy-file-item').removeClass('selected');
        selectedFileId = 0;
        $('#meta-content').hide();
        $('#meta-empty').show();
    }

    // 5. Lightbox
    function openLightbox(url) {
        $('#lightbox-img').attr('src', url);
        $('#studiofy-lightbox').removeClass('studiofy-hidden');
    }

    // Click off to deselect
    $('#file-grid').click(function(e) {
        if (!$(e.target).closest('.studiofy-file-item').length) clearSelection();
    });

    // Upload
    $('#btn-upload-media').click(() => $('#file-input').click());
    $('#file-input').change(function() {
        const max = parseInt(studiofyGallerySettings.max_upload_size);
        for(let i=0; i<this.files.length; i++) {
            if(this.files[i].size > max) {
                alert('File too large.');
                return;
            }
        }
        $('#upload-form').submit();
    });
});
