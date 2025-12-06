/**
 * Studiofy Gallery Explorer
 * @package Studiofy
 * @version 2.2.8
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
        const hasPage = $(this).data('has-page'); 
        
        $('#current-folder-label').text($(this).text().trim());
        $('#upload-gallery-id').val(currentGalleryId);
        $('#btn-upload-media').prop('disabled', false);
        
        if(hasPage === 'true') {
             $('#btn-create-page').prop('disabled', true).text('Page Exists');
        } else {
             $('#btn-create-page').prop('disabled', false).text('Create Private Gallery Page');
        }
        
        loadFiles(currentGalleryId);
        clearSelection();
    });

    // 2. Create Page Click (FIXED)
    $('#btn-create-page').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        btn.text('Creating...').prop('disabled', true);

        $.post(studiofyGallerySettings.root + '../admin-ajax.php', {
            action: 'studiofy_create_gallery_page',
            id: currentGalleryId,
            nonce: studiofyGallerySettings.nonce
        }, function(response) {
            if(response.success) {
                alert('Page created! Redirecting...');
                // Redirect to the new page
                window.location.href = response.data.redirect_url;
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
                btn.prop('disabled', false).text('Create Private Gallery Page');
            }
        });
    });

    // 3. Load Files
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
            
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                $('.studiofy-file-item').removeClass('selected');
                item.classList.add('selected');
                selectedFileId = f.id;
                showMeta(f);
            });

            item.addEventListener('dblclick', function(e) {
                if(isImg) openLightbox(f.file_url);
            });

            item.querySelector('.file-trash-overlay').addEventListener('click', function(e) {
                e.stopPropagation();
                deleteFile(f.id, item);
            });

            fragment.appendChild(item);
        });

        grid.appendChild(fragment);
    }

    // 4. Sidebar
    function showMeta(f) {
        $('#meta-empty').hide();
        $('#meta-content').show();
        $('.studiofy-meta-sidebar').addClass('open');
        
        $('#inp-meta-title').val(f.meta_title || f.file_name);
        $('#inp-meta-author').val(f.meta_photographer || '');
        $('#inp-meta-project').val(f.meta_project || '');
        
        $('#meta-size').text(f.file_size);
        $('#meta-type').text(f.file_type.toUpperCase());
        $('#meta-dims').text(f.dimensions || 'N/A');
        
        const isImg = ['jpg','jpeg','png','gif'].includes(f.file_type.toLowerCase());
        $('#meta-preview').html(isImg ? `<img src="${f.file_url}" loading="lazy">` : '');
        
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
            deleteFile(f.id, $(`.studiofy-file-item[data-id="${f.id}"]`)[0]);
        });
    }

    function clearSelection() {
        $('.studiofy-file-item').removeClass('selected');
        selectedFileId = 0;
        $('#meta-content').hide();
        $('#meta-empty').show();
        $('.studiofy-meta-sidebar').removeClass('open');
    }

    function deleteFile(id, domElement) {
        if(!confirm('Permanently delete this file?')) return;
        
        wp.apiFetch({
            path: '/studiofy/v1/galleries/files/' + id,
            method: 'DELETE',
            headers: { 'X-WP-Nonce': studiofyGallerySettings.nonce }
        }).then(() => {
            if(domElement) domElement.remove();
            if(selectedFileId === id) clearSelection();
        });
    }

    function openLightbox(url) {
        $('#lightbox-img').attr('src', url);
        $('#studiofy-lightbox').removeClass('studiofy-hidden');
    }
    
    $('.close-meta').click(clearSelection);
    
    $('.close-modal').click(function() {
        $(this).closest('.studiofy-modal-overlay').addClass('studiofy-hidden');
        $('#lightbox-img').attr('src', '');
    });

    // Upload
    $('#btn-upload-media').click(function() {
        if(currentGalleryId === 0) return;
        $('#file-input').click();
    });

    $('#file-input').change(function() {
        const files = this.files;
        const max = parseInt(studiofyGallerySettings.max_upload_size);
        
        for(let i=0; i<files.length; i++) {
            if(files[i].size > max) {
                alert(`File ${files[i].name} is too large.`);
                $(this).val('');
                return;
            }
        }
        
        if(files.length > 0) {
            $('#upload-form').submit();
        }
    });

    $('#file-grid').click(function(e) {
        if(e.target.id === 'file-grid') clearSelection();
    });
});
