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
            item.innerHTML = `
                <span class="file-type-overlay">${f.file_type}</span>
                ${thumb ? `<img src="${thumb}" class="file-preview">` : '<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#eee;">RAW</div>'}
                <button class="file-trash-overlay"><span class="dashicons dashicons-trash"></span></button>
            `;
            // Add click events... (Standard event listeners here)
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
});
