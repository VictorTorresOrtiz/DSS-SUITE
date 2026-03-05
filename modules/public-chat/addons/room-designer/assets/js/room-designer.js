jQuery(document).ready(function ($) {
    // Only run in room-designer mode
    if (typeof dssPublicChat === 'undefined' || dssPublicChat.mode !== 'room-designer') return;

    var $container = $('#dss-public-chat-container');
    var $history = $('#dss-public-chat-history');
    var $form = $('#dss-rd-form');
    var $dropzone = $('#dss-rd-dropzone');
    var $preview = $container.find('.dss-rd-preview');
    var $sendBtn = $form.find('.dss-rd-send-btn');
    var selectedFile = null;

    // File input
    $container.on('change', '#dss-rd-file', function () {
        if (this.files[0]) loadPreview(this.files[0]);
    });

    // Drag & Drop
    $dropzone.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('dragover');
    }).on('dragleave drop', function (e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    }).on('drop', function (e) {
        var file = e.originalEvent.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) loadPreview(file);
    });

    // Change photo
    $container.on('click', '.dss-rd-change-img', function () {
        selectedFile = null;
        $preview.hide();
        $dropzone.show();
        $sendBtn.prop('disabled', true);
        $container.find('#dss-rd-file').val('');
    });

    function loadPreview(file) {
        selectedFile = file;
        var reader = new FileReader();
        reader.onload = function (e) {
            $container.find('#dss-rd-preview-img').attr('src', e.target.result);
            $dropzone.hide();
            $preview.show();
            $sendBtn.prop('disabled', false);
        };
        reader.readAsDataURL(file);
    }

    // Submit
    $form.on('submit', function (e) {
        e.preventDefault();
        if (!selectedFile) return;

        var notes = $form.find('textarea').val();

        // Hide upload zone, show processing in chat
        $dropzone.hide();
        $preview.hide();

        // Show user message with thumbnail
        var reader = new FileReader();
        reader.onload = function (ev) {
            $history.append(
                '<div class="dss-message dss-user-message">' +
                '<img src="' + ev.target.result + '" style="max-width:150px;border-radius:8px;display:block;margin-bottom:6px;">' +
                (notes ? '<p>' + escapeHtml(notes) : '<p>Diseña mi habitación') +
                '</p></div>'
            );

            // Show loading
            var $loading = $(
                '<div class="dss-message dss-bot-message dss-rd-loading-msg">' +
                '<div class="dss-rd-spinner-inline"></div>' +
                '<span>Analizando tu habitación y seleccionando muebles...</span>' +
                '</div>'
            );
            $history.append($loading);
            $history.scrollTop($history[0].scrollHeight);

            // Send request
            var formData = new FormData();
            formData.append('action', 'dss_room_designer');
            formData.append('nonce', dssRoomDesigner.nonce);
            formData.append('room_image', selectedFile);
            if (notes) formData.append('notes', notes);

            $.ajax({
                url: dssRoomDesigner.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 90000,
                success: function (response) {
                    $loading.remove();
                    if (!response.success) {
                        appendBotMessage(response.data.message || 'Error al generar el diseño.');
                        resetUpload();
                        return;
                    }
                    renderResult(response.data);
                    resetUpload();
                },
                error: function () {
                    $loading.remove();
                    appendBotMessage('Error de conexión. Inténtalo de nuevo.');
                    resetUpload();
                }
            });
        };
        reader.readAsDataURL(selectedFile);

        $sendBtn.prop('disabled', true);
    });

    function renderResult(data) {
        var html = '<div class="dss-message dss-bot-message dss-rd-result-msg">';

        if (data.image) {
            html += '<div class="dss-rd-gen-image"><img src="' + data.image + '" alt="Diseño generado"></div>';
        }

        if (data.text) {
            var text = escapeHtml(data.text)
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            html += '<div class="dss-rd-text">' + text + '</div>';
        }

        if (data.products && data.products.length > 0) {
            html += '<div class="dss-rd-products"><strong>Productos Sugeridos:</strong><div class="dss-rd-product-grid">';
            data.products.forEach(function (p) {
                html += '<a href="' + p.url + '" target="_blank" class="dss-rd-product-card">' +
                    '<img src="' + p.image + '" alt="' + escapeHtml(p.name) + '">' +
                    '<span class="dss-rd-product-name">' + escapeHtml(p.name) + '</span>' +
                    '<span class="dss-rd-product-price">' + p.price + '€</span>' +
                    '</a>';
            });
            html += '</div></div>';
        }

        html += '</div>';
        $history.append(html);
        $history.scrollTop($history[0].scrollHeight);
    }

    function resetUpload() {
        selectedFile = null;
        $dropzone.show();
        $preview.hide();
        $sendBtn.prop('disabled', true);
        $form.find('textarea').val('');
        $container.find('#dss-rd-file').val('');
    }

    function appendBotMessage(text) {
        $history.append('<div class="dss-message dss-bot-message">' + escapeHtml(text) + '</div>');
        $history.scrollTop($history[0].scrollHeight);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
