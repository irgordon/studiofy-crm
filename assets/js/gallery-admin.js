/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.2.1
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;
    let selectedFileId = 0;

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

    function loadFiles(id) {
        $('#file-grid').html('<p style="padding:20px;">Loading...</p>');
        wp.apiFetch({ path: '/studiofy/v1/galleries/' + id + '/files' }).then(renderGrid).catch(err => {
            $('#file-grid').html('<p style="color:red; padding:20px;">Error loading files.</p>');
        });
    }

    function renderGrid(files) {
        const grid = document.getElementById('file-grid');
        grid.innerHTML = '';
        
        if (files.length === 0) {
            grid.innerHTML = '<div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>This folder is empty.</p></div>';
            return;
        }

        // Performance: Use DocumentFragment
        const fragment = document.createDocumentFragment();

        files.forEach(f => {
            const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
            const thumb = isImg ? f.file_url : '';
            
            const item = document.createElement('div');
            item.className = 'studiofy-file-item';
            item.dataset.id = f.id;
            
            const thumbHtml = thumb 
                ? `<img src="${thumb}" class="file-preview" loading="lazy" decoding="async">` 
                : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#eee; color:#555;">RAW</div>';

            item.innerHTML = `
                <span class="file-type-overlay">${f.file_type}</span>
                ${thumbHtml}
                <div class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></div>
            `;
            
            // Native Event Listeners
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                item.classList.add('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            item.querySelector('.file-trash-overlay').addEventListener('click', function(e) {
                e.stopPropagation();
                deleteFile(f.id, item);
            });
            
            fragment.appendChild(item);
        });

        grid.appendChild(fragment);
    }

    function showMeta(f) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        
        $('#inp-meta-title').val(f.meta_title || f.file_name);
        $('#inp-meta-author').val(f.meta_photographer || '');
        $('#inp-meta-project').val(f.meta_project || '');
        $('#meta-size').text(f.file_size);
        $('#meta-type').text(f.file_type);
        $('#meta-dims').text(f.dimensions || '-');
        
        const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
        $('#meta-preview').html(isImg ? `<img src="${f.file_url}" loading="lazy">` : '');
        
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

    function openLightbox(url) {
        $('#lightbox-img').attr('src', url);
        $('#studiofy-lightbox').removeClass('studiofy-hidden');
    }

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

    $('#file-input').change(function() {
        if(this.files.length) $('#upload-form').submit();
    });
    
    $('#btn-upload-media').click(() => $('#file-input').click());
    $('.close-meta').click(function(){ $('#meta-sidebar').hide(); });
    // Lightbox close handled in PHP/HTML onclick
});
