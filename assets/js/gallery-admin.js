/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.2.23
 */
jQuery(document).ready(function($) {
    let currentGalleryId = 0;
    const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB

    $('.folder-item').click(function(e) {
        e.stopPropagation();
        $('.folder-item').removeClass('active');
        $(this).addClass('active');
        currentGalleryId = $(this).data('id');
        const hasPage = $(this).data('has-page');
        $('#current-folder-label').text($(this).text().trim());
        $('#btn-upload-media').prop('disabled', false);
        if(hasPage === 'true') {
             $('#btn-create-page').prop('disabled', true).text('Page Exists');
        } else {
             $('#btn-create-page').prop('disabled', false).text('Create Private Gallery Page');
        }
        loadFiles(currentGalleryId);
    });

    $('#btn-create-page').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        btn.text('Creating...').prop('disabled', true);
        $.post(studiofyGallerySettings.ajax_url, {
            action: 'studiofy_create_gallery_page',
            id: currentGalleryId,
            nonce: studiofyGallerySettings.nonce
        }, function(res) {
            if(res.success) window.location.href = res.data.redirect_url;
            else alert('Error: ' + res.data);
        });
    });

    function loadFiles(id) {
        $('#file-grid').html('<p style="padding:20px;">Loading...</p>');
        wp.apiFetch({ path: '/studiofy/v1/galleries/' + id + '/files' }).then(renderGrid);
    }

    function renderGrid(files) {
        const grid = document.getElementById('file-grid');
        grid.innerHTML = '';
        if (files.length === 0) {
            grid.innerHTML = '<div class="studiofy-empty-state-small"><span class="dashicons dashicons-format-gallery"></span><p>This folder is empty.</p></div>';
            return;
        }
        const frag = document.createDocumentFragment();
        files.forEach(f => {
            const thumb = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase()) ? f.file_url : '';
            const item = document.createElement('div');
            item.className = 'studiofy-file-item';
            item.dataset.id = f.id;
            // Accessibility
            item.setAttribute('role', 'button');
            item.setAttribute('aria-label', f.file_name);
            item.setAttribute('tabindex', '0');

            item.innerHTML = `
                <span class="file-type-overlay">${f.file_type}</span>
                ${thumb ? `<img src="${thumb}" class="file-preview">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#eee;">RAW</div>'}
                <button class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></button>
            `;
            
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                item.classList.add('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            item.addEventListener('dblclick', function(e) {
                if(thumb) openLightbox(f.file_url);
            });

            item.querySelector('.file-trash-overlay').addEventListener('click', function(e) {
                e.stopPropagation();
                deleteFile(f.id, item);
            });

            frag.appendChild(item);
        });
        grid.appendChild(frag);
    }

    // UPLOAD LOGIC
    $('#btn-upload-media').click(() => $('#file-input').click());

    $('#file-input').change(async function() {
        const files = this.files;
        if (files.length === 0) return;

        $('#modal-upload-progress').removeClass('studiofy-hidden');
        
        for (let i = 0; i < files.length; i++) {
            await uploadFileInChunks(files[i], i + 1, files.length);
        }

        $('#modal-upload-progress').addClass('studiofy-hidden');
        $('#file-input').val(''); 
        loadFiles(currentGalleryId);
    });

    async function uploadFileInChunks(file, currentIdx, totalFiles) {
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        $('#upload-status-text').text(`Uploading ${currentIdx}/${totalFiles}: ${file.name}`);

        for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
            const start = chunkIdx * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('action', 'studiofy_gallery_upload_chunk');
            formData.append('nonce', studiofyGallerySettings.nonce);
            formData.append('gallery_id', currentGalleryId);
            formData.append('file_name', file.name);
            formData.append('chunk_index', chunkIdx);
            formData.append('total_chunks', totalChunks);
            formData.append('file_chunk', chunk);

            await $.ajax({
                url: studiofyGallerySettings.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (!res.success) throw new Error(res.data);
                    const overallPercent = ((chunkIdx + 1) / totalChunks) * 100;
                    $('#studiofy-progress-bar').css('width', overallPercent + '%');
                }
            });
        }
    }

    // Helper functions
    let selectedFileId = 0;
    function showMeta(f) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        $('.studiofy-meta-sidebar').addClass('open');
        $('#inp-meta-title').val(f.meta_title || f.file_name);
        $('#inp-meta-author').val(f.meta_photographer || '');
        $('#inp-meta-project').val(f.meta_project || '');
        $('#meta-size').text(f.file_size);
        $('#meta-type').text(f.file_type);
        $('#meta-dims').text(f.dimensions || '-');
        const img = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
        $('#meta-preview').html(img ? `<img src="${f.file_url}">` : '');
        
        $('#btn-save-meta').off('click').click(function() {
            wp.apiFetch({
                path: '/studiofy/v1/galleries/files/' + f.id,
                method: 'POST',
                data: {
                    meta_title: $('#inp-meta-title').val(),
                    meta_photographer: $('#inp-meta-author').val(),
                    meta_project: $('#inp-meta-project').val()
                }
            }).then(() => alert('Saved'));
        });
        
        $('#btn-view-large').off('click').click(function() {
            if(img) openLightbox(f.file_url);
        });
        
        $('#btn-delete-file').off('click').click(function() {
            deleteFile(f.id, $(`.studiofy-file-item[data-id="${f.id}"]`));
        });
    }

    function deleteFile(id, domEl) {
        if(!confirm('Delete file?')) return;
        wp.apiFetch({
            path: '/studiofy/v1/galleries/files/' + id,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': studiofyGallerySettings.nonce }
        }).then(() => {
            if(domEl) domEl.remove();
            clearSelection();
        });
    }

    function clearSelection() {
        $('.studiofy-file-item').removeClass('selected');
        selectedFileId = 0;
        $('#meta-content').hide();
        $('#meta-empty').show();
        $('.studiofy-meta-sidebar').removeClass('open');
    }

    function openLightbox(url) {
        $('#lightbox-img').attr('src', url);
        $('#studiofy-lightbox').removeClass('studiofy-hidden');
    }
    
    $('.close-meta').click(clearSelection);
    $('.close-modal').click(function() { $(this).closest('.studiofy-modal-overlay').addClass('studiofy-hidden'); });
    $('#file-grid').click(function(e) { if(e.target.id === 'file-grid') clearSelection(); });
});
