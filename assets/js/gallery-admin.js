/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.2.2
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;
    let selectedFileId = 0;

    // 1. Folder Navigation
    $('.folder-item').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // UI Updates
        $('.folder-item').removeClass('active');
        $(this).addClass('active');
        
        currentGalleryId = $(this).data('id');
        const folderName = $(this).text().trim();
        
        $('#current-folder-label').text(folderName);
        $('#upload-gallery-id').val(currentGalleryId);
        $('#btn-upload-media').prop('disabled', false);
        
        // Fetch
        loadFiles(currentGalleryId);
        deselectAll();
    });

    // 2. Fetch Files
    function loadFiles(id) {
        const grid = $('#file-grid');
        grid.html('<p style="padding:20px; grid-column:1/-1;">Loading images...</p>');
        
        wp.apiFetch({ path: '/studiofy/v1/galleries/' + id + '/files' })
            .then(renderGrid)
            .catch(err => {
                grid.html('<p style="color:#d63638; padding:20px; grid-column:1/-1;">Error loading files.</p>');
                console.error(err);
            });
    }

    // 3. Render Grid
    function renderGrid(files) {
        const grid = document.getElementById('file-grid');
        grid.innerHTML = ''; // Clear

        if (files.length === 0) {
            grid.innerHTML = `
                <div class="studiofy-empty-state-small">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <p>This folder is empty. Upload images to get started.</p>
                </div>`;
            return;
        }

        const fragment = document.createDocumentFragment();

        files.forEach(f => {
            const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
            const thumb = isImg ? f.file_url : '';
            
            const item = document.createElement('div');
            item.className = 'studiofy-file-item';
            item.dataset.id = f.id;

            const preview = thumb 
                ? `<img src="${thumb}" class="file-preview" loading="lazy">`
                : `<div style="height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#eee; color:#555; font-weight:bold;"><span>${f.file_type.toUpperCase()}</span></div>`;

            item.innerHTML = `
                <span class="file-type-overlay">${f.file_type}</span>
                ${preview}
                <div class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></div>
            `;
            
            // Interaction: Select
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                item.classList.add('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            // Interaction: Double Click
            item.addEventListener('dblclick', function(e) {
                if(isImg) openLightbox(f.file_url);
            });

            // Interaction: Delete
            const trashBtn = item.querySelector('.file-trash-overlay');
            if(trashBtn) {
                trashBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    deleteFile(f.id, item);
                });
            }

            fragment.appendChild(item);
        });

        grid.appendChild(fragment);
    }

    // 4. Sidebar Logic
    function showMeta(f) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        $('.studiofy-meta-sidebar').addClass('open');
        
        // Populate
        $('#inp-meta-title').val(f.meta_title || f.file_name);
        $('#inp-meta-author').val(f.meta_photographer || '');
        $('#inp-meta-project').val(f.meta_project || '');
        
        $('#meta-size').text(f.file_size);
        $('#meta-type').text(f.file_type.toUpperCase());
        $('#meta-dims').text(f.dimensions || 'N/A');
        
        const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
        $('#meta-preview').html(isImg ? `<img src="${f.file_url}">` : '<div style="padding:40px; background:#eee; text-align:center;">No Preview</div>');

        // Bind Actions
        $('#btn-save-meta').off('click').click(function() {
            const btn = $(this);
            btn.text('Saving...').prop('disabled', true);
            
            wp.apiFetch({
                path: '/studiofy/v1/galleries/files/' + f.id,
                method: 'POST',
                data: {
                    meta_title: $('#inp-meta-title').val(),
                    meta_photographer: $('#inp-meta-author').val(),
                    meta_project: $('#inp-meta-project').val()
                }
            }).then(() => {
                btn.text('Saved!').prop('disabled', false);
                setTimeout(() => btn.text('Save Metadata'), 2000);
            });
        });

        $('#btn-view-large').off('click').click(function() {
            if(isImg) openLightbox(f.file_url);
        });

        $('#btn-delete-file').off('click').click(function() {
            deleteFile(f.id, $(`.studiofy-file-item[data-id="${f.id}"]`));
        });
    }

    function deselectAll() {
        $('.studiofy-file-item').removeClass('selected');
        selectedFileId = 0;
        $('#meta-content').hide();
        $('#meta-empty').show();
        $('.studiofy-meta-sidebar').removeClass('open');
    }

    function deleteFile(id, domEl) {
        if(!confirm('Permanently delete this file? This cannot be undone.')) return;
        
        wp.apiFetch({
            path: '/studiofy/v1/galleries/files/' + id,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': studiofyGallerySettings.nonce }
        }).then(() => {
            if(domEl) domEl.remove();
            if(selectedFileId === id) deselectAll();
        });
    }

    // 5. Lightbox
    function openLightbox(url) {
        $('#lightbox-img').attr('src', url);
        $('#studiofy-lightbox').removeClass('studiofy-hidden');
    }
    
    // Close handlers
    $('.close-meta').click(deselectAll);
    
    $('.close-modal').click(function() {
        $(this).closest('.studiofy-modal-overlay').addClass('studiofy-hidden');
        $('#lightbox-img').attr('src', '');
    });

    // 6. Upload
    $('#btn-upload-media').click(function() {
        if(currentGalleryId === 0) return;
        $('#file-input').click();
    });

    $('#file-input').change(function() {
        const files = this.files;
        const max = parseInt(studiofyGallerySettings.max_upload_size);
        
        for(let i=0; i<files.length; i++) {
            if(files[i].size > max) {
                alert(`File ${files[i].name} is too large. Max size: ${Math.round(max/1024/1024)}MB`);
                $(this).val('');
                return;
            }
        }
        
        if(files.length > 0) {
            $('#upload-form').submit();
        }
    });

    // Click Outside to Deselect
    $('#file-grid').click(function(e) {
        if(e.target.id === 'file-grid') deselectAll();
    });
});
