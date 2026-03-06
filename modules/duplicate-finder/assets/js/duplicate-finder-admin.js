(function ($) {
    'use strict';

    var $scanBtn = $('#dss-dupfinder-scan');
    var $results = $('#dss-dupfinder-results');
    var $list = $('#dss-dupfinder-list');
    var $count = $('#dss-dupfinder-count');
    var $hint = $('#dss-dupfinder-hint');
    var $loading = $('#dss-dupfinder-loading');

    // ── Scan ──
    $scanBtn.on('click', function () {
        var criteria = $('#dss-dupfinder-criteria').val();
        var status = $('#dss-dupfinder-status').val();

        $scanBtn.prop('disabled', true);
        $results.hide();
        $loading.show();
        $list.empty();

        $.post(dssDupFinder.ajaxUrl, {
            action: 'dss_dupfinder_scan',
            nonce: dssDupFinder.nonce,
            criteria: criteria,
            status: status
        }, function (res) {
            $loading.hide();
            $scanBtn.prop('disabled', false);

            if (!res.success) {
                alert('Error: ' + (res.data || 'Desconocido'));
                return;
            }

            var data = res.data;
            $results.show();

            if (data.groups.length === 0) {
                $count.text('0 duplicados');
                $hint.text('');
                $list.html(
                    '<div class="dss-dupfinder-empty">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<h3>Sin duplicados</h3>' +
                    '<p>No se encontraron productos duplicados con los criterios seleccionados.</p>' +
                    '</div>'
                );
                return;
            }

            $count.text(data.group_count + ' grupo' + (data.group_count > 1 ? 's' : '') + ' (' + data.total + ' productos)');
            $hint.text('Revisa cada grupo y elimina o edita los duplicados.');

            renderGroups(data.groups);
        }).fail(function () {
            $loading.hide();
            $scanBtn.prop('disabled', false);
            alert('Error de conexión.');
        });
    });

    // ── Render Groups ──
    function renderGroups(groups) {
        $list.empty();

        groups.forEach(function (group) {
            var $group = $('<div class="dss-dup-group"></div>');

            var $header = $(
                '<div class="dss-dup-group-header">' +
                '<span class="dss-dup-group-reason">' + escHtml(group.reason) + '</span>' +
                '<span class="dss-dup-group-match">' + escHtml(group.match) + '</span>' +
                '<span style="margin-left:auto;font-size:13px;color:#92400e;">' + group.items.length + ' productos</span>' +
                '</div>'
            );
            $group.append($header);

            group.items.forEach(function (item) {
                var thumb = item.thumbnail
                    ? '<img class="dss-dup-thumb" src="' + escAttr(item.thumbnail) + '" alt="">'
                    : '<div class="dss-dup-thumb-placeholder"><span class="dashicons dashicons-format-image"></span></div>';

                var statusLabel = {
                    publish: 'Publicado',
                    draft: 'Borrador',
                    pending: 'Pendiente',
                    private: 'Privado'
                };

                var stockLabel = item.stock === 'instock' ? 'En stock' : (item.stock === 'outofstock' ? 'Agotado' : item.stock);

                var $item = $(
                    '<div class="dss-dup-item" data-id="' + item.id + '">' +
                    thumb +
                    '<div class="dss-dup-info">' +
                    '<div class="dss-dup-title">' + escHtml(item.title || '(Sin título)') +
                    (item.lang ? ' <span class="dss-dup-lang">' + escHtml(item.lang.toUpperCase()) + '</span>' : '') +
                    '</div>' +
                    '<div class="dss-dup-meta">' +
                    '<span>ID: ' + item.id + '</span>' +
                    (item.sku ? '<span>SKU: ' + escHtml(item.sku) + '</span>' : '') +
                    (item.price ? '<span>' + escHtml(item.price) + ' &euro;</span>' : '') +
                    '<span>' + escHtml(stockLabel || '') + '</span>' +
                    '</div>' +
                    '</div>' +
                    '<span class="dss-dup-status ' + escAttr(item.status) + '">' + escHtml(statusLabel[item.status] || item.status) + '</span>' +
                    '<div class="dss-dup-actions">' +
                    '<a href="' + escAttr(item.edit_url) + '" class="button" target="_blank" title="Editar">' +
                    '<span class="dashicons dashicons-edit" style="font-size:16px;width:16px;height:16px;"></span> Editar</a>' +
                    '<a href="' + escAttr(item.view_url) + '" class="button" target="_blank" title="Ver">' +
                    '<span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;"></span></a>' +
                    '<button type="button" class="button dss-btn-trash" title="Mover a papelera" data-id="' + item.id + '">' +
                    '<span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;"></span></button>' +
                    '</div>' +
                    '</div>'
                );

                $group.append($item);
            });

            $list.append($group);
        });
    }

    // ── Trash product ──
    $list.on('click', '.dss-btn-trash', function () {
        var $btn = $(this);
        var postId = $btn.data('id');
        var $item = $btn.closest('.dss-dup-item');
        var title = $item.find('.dss-dup-title').text();

        if (!confirm('¿Mover "' + title + '" (ID: ' + postId + ') a la papelera?')) {
            return;
        }

        $btn.prop('disabled', true);

        $.post(dssDupFinder.ajaxUrl, {
            action: 'dss_dupfinder_trash',
            nonce: dssDupFinder.nonce,
            post_id: postId
        }, function (res) {
            if (res.success) {
                $item.addClass('trashed');
                $item.find('.dss-dup-status').text('Papelera').attr('class', 'dss-dup-status draft');
            } else {
                alert('Error: ' + (res.data || 'No se pudo eliminar'));
                $btn.prop('disabled', false);
            }
        }).fail(function () {
            alert('Error de conexión.');
            $btn.prop('disabled', false);
        });
    });

    // ── Helpers ──
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

})(jQuery);
