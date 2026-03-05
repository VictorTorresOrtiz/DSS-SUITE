jQuery(document).ready(function ($) {

    var $chatWindow = $('#dss-public-chat-window');
    if (!$chatWindow.length) return;

    // Inject Room Designer button into chat
    var $footer = $chatWindow.find('.dss-public-chat-footer');
    var $form = $footer.find('#dss-public-chat-form');

    var $designerBtn = $(
        '<button type="button" id="dss-room-designer-btn" class="dss-room-designer-trigger" title="Diseñar Habitación">' +
        '<span class="dashicons dashicons-admin-home"></span>' +
        '</button>'
    );
    $form.find('.dss-upload-label').after($designerBtn);

    // Designer modal overlay
    var $overlay = $(
        '<div class="dss-rd-overlay" style="display:none;">' +
            '<div class="dss-rd-modal">' +
                '<div class="dss-rd-header">' +
                    '<span class="dashicons dashicons-admin-home"></span>' +
                    '<h3>Room Designer</h3>' +
                    '<button class="dss-rd-close">&times;</button>' +
                '</div>' +
                '<div class="dss-rd-body">' +
                    '<div class="dss-rd-upload-zone" id="dss-rd-dropzone">' +
                        '<span class="dashicons dashicons-format-image"></span>' +
                        '<p>Arrastra una foto de tu habitación aquí</p>' +
                        '<span class="dss-rd-or">o</span>' +
                        '<label class="button button-secondary">' +
                            'Seleccionar Archivo' +
                            '<input type="file" id="dss-rd-file" accept="image/*" style="display:none;">' +
                        '</label>' +
                    '</div>' +
                    '<div class="dss-rd-preview" style="display:none;">' +
                        '<img id="dss-rd-preview-img" src="">' +
                        '<button class="dss-rd-change-img button button-small">Cambiar foto</button>' +
                    '</div>' +
                '</div>' +
                '<div class="dss-rd-footer">' +
                    '<button class="button button-primary button-large dss-rd-generate" disabled>' +
                        '<span class="dashicons dashicons-art" style="vertical-align:middle;"></span> Generar Diseño' +
                    '</button>' +
                '</div>' +
                '<div class="dss-rd-result" style="display:none;">' +
                    '<div class="dss-rd-result-content"></div>' +
                '</div>' +
                '<div class="dss-rd-loading" style="display:none;">' +
                    '<div class="dss-rd-spinner"></div>' +
                    '<p>Analizando tu habitación y seleccionando muebles...</p>' +
                    '<small>Esto puede tardar unos segundos</small>' +
                '</div>' +
            '</div>' +
        '</div>'
    );
    $('body').append($overlay);

    var selectedFile = null;

    // Open modal
    $designerBtn.on('click', function () {
        $overlay.fadeIn(200);
        resetModal();
    });

    // Close modal
    $overlay.on('click', '.dss-rd-close', function () {
        $overlay.fadeOut(200);
    });
    $overlay.on('click', function (e) {
        if ($(e.target).is('.dss-rd-overlay')) $overlay.fadeOut(200);
    });

    // File selection
    $overlay.on('change', '#dss-rd-file', function () {
        var file = this.files[0];
        if (file) loadPreview(file);
    });

    // Drag & Drop
    var $dropzone = $overlay.find('#dss-rd-dropzone');
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
    $overlay.on('click', '.dss-rd-change-img', function () {
        resetModal();
    });

    function loadPreview(file) {
        selectedFile = file;
        var reader = new FileReader();
        reader.onload = function (e) {
            $overlay.find('#dss-rd-preview-img').attr('src', e.target.result);
            $overlay.find('.dss-rd-upload-zone').hide();
            $overlay.find('.dss-rd-preview').show();
            $overlay.find('.dss-rd-generate').prop('disabled', false);
        };
        reader.readAsDataURL(file);
    }

    function resetModal() {
        selectedFile = null;
        $overlay.find('.dss-rd-upload-zone').show();
        $overlay.find('.dss-rd-preview').hide();
        $overlay.find('.dss-rd-result').hide();
        $overlay.find('.dss-rd-loading').hide();
        $overlay.find('.dss-rd-body, .dss-rd-footer').show();
        $overlay.find('.dss-rd-generate').prop('disabled', true);
        $overlay.find('#dss-rd-file').val('');
    }

    // Generate
    $overlay.on('click', '.dss-rd-generate', function () {
        if (!selectedFile) return;

        $overlay.find('.dss-rd-body, .dss-rd-footer').hide();
        $overlay.find('.dss-rd-result').hide();
        $overlay.find('.dss-rd-loading').show();

        var formData = new FormData();
        formData.append('action', 'dss_room_designer');
        formData.append('nonce', dssRoomDesigner.nonce);
        formData.append('room_image', selectedFile);

        $.ajax({
            url: dssRoomDesigner.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 90000,
            success: function (response) {
                $overlay.find('.dss-rd-loading').hide();

                if (!response.success) {
                    $overlay.find('.dss-rd-body, .dss-rd-footer').show();
                    if (window.dssNotify) {
                        window.dssNotify({ title: 'Room Designer', message: response.data.message || 'Error al generar.', type: 'error' });
                    }
                    return;
                }

                renderResult(response.data);
            },
            error: function () {
                $overlay.find('.dss-rd-loading').hide();
                $overlay.find('.dss-rd-body, .dss-rd-footer').show();
                if (window.dssNotify) {
                    window.dssNotify({ title: 'Room Designer', message: 'Error de conexión. Inténtalo de nuevo.', type: 'error' });
                }
            }
        });
    });

    function renderResult(data) {
        var $result = $overlay.find('.dss-rd-result');
        var $content = $result.find('.dss-rd-result-content');
        $content.empty();

        // Generated image
        if (data.image) {
            $content.append('<div class="dss-rd-gen-image"><img src="' + data.image + '" alt="Diseño generado"></div>');
        }

        // AI text response (parse markdown-like)
        if (data.text) {
            var html = escapeHtml(data.text)
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            $content.append('<div class="dss-rd-text">' + html + '</div>');
        }

        // Product cards
        if (data.products && data.products.length > 0) {
            var productsHtml = '<div class="dss-rd-products"><h4>Productos Sugeridos</h4><div class="dss-rd-product-grid">';
            data.products.forEach(function (p) {
                productsHtml += '<a href="' + p.url + '" target="_blank" class="dss-rd-product-card">' +
                    '<img src="' + p.image + '" alt="' + escapeHtml(p.name) + '">' +
                    '<span class="dss-rd-product-name">' + escapeHtml(p.name) + '</span>' +
                    '<span class="dss-rd-product-price">' + p.price + '€</span>' +
                    '</a>';
            });
            productsHtml += '</div></div>';
            $content.append(productsHtml);
        }

        // Back button
        $content.append('<div style="text-align:center;margin-top:20px;"><button class="button dss-rd-back">Diseñar otra habitación</button></div>');

        $result.show();
    }

    $overlay.on('click', '.dss-rd-back', function () {
        resetModal();
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
