jQuery(function($){
    $('#dprc-tabs').tabs();

    // color picker
    $('.dprc-color').wpColorPicker();

    // media uploader for fallback/default
    var mediaUploader;
    $('.dprc-upload-btn').on('click', function(e){
        e.preventDefault();
        var target = $($(this).attr('data-target'));
        if (mediaUploader) {
            mediaUploader.open();
            mediaUploader.on('select', function(){});
            return;
        }
        mediaUploader = wp.media({
            title: 'Select file',
            button: { text: 'Use this file' },
            multiple: false
        });

        mediaUploader.on('select', function(){
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            target.val(attachment.url).trigger('change');
        });

        mediaUploader.open();
    });

    // live preview: simple render using current values
    function renderPreview() {
        var opts = dprcAdmin.options || {};
        var acc = $('#dprc_accent').val() || opts.accent || '#e50914';
        var timeout = $('#dprc_timeout').val() || opts.timeout || 5;
        var default_url = $('#dprc_default_url').val() || opts.default_url || '';
        var fallback_url = $('#dprc_fallback_url').val() || opts.fallback_url || '';
        var preview = $('#dprc-preview');
        preview.empty();
        var html = '<div style="background:'+acc+';padding:12px;border-radius:8px">';
        html += '<div style="display:flex;gap:12px;align-items:center">';
        var img = default_url || fallback_url || '';
        if (img) {
            html += '<img src="'+img+'" style="width:80px;height:110px;object-fit:cover;border-radius:6px;margin-right:12px">';
        } else {
            html += '<div style="width:80px;height:110px;background:#333;border-radius:6px;margin-right:12px"></div>';
        }
        html += '<div><div style="font-weight:700">Preview Title</div><div style="color:#fff;opacity:.8;margin-top:6px">Timer: '+timeout+'s</div></div></div></div>';
        preview.append(html);
    }

    // update preview when fields change
    $('#dprc_timeout, #dprc_accent, #dprc_default_url, #dprc_fallback_url').on('change keyup', renderPreview);
    renderPreview();
});
