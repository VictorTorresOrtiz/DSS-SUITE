(function ($) {
    'use strict';

    var $scanBtn = $('#dss-dupfinder-scan');
    var $results = $('#dss-dupfinder-results');
    var $list = $('#dss-dupfinder-list');
    var $count = $('#dss-dupfinder-count');
    var $loading = $('#dss-dupfinder-loading');
    var $bulkTrash = $('#dss-dupfinder-bulk-trash');
    var $rollback = $('#dss-dupfinder-rollback');
    var $pagination = $('#dss-dupfinder-pagination');

    var lastGroups = [];
    var currentPage = 1;
    var perPage = 5;

    // ── Scan ──
    $scanBtn.on('click', function () {
        var criteria = $('#dss-dupfinder-criteria').val();
        var status = $('#dss-dupfinder-status').val();

        $scanBtn.prop('disabled', true);
        $results.hide();
        $bulkTrash.hide();
        $rollback.hide();
        $pagination.hide();
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
                lastGroups = [];
                $count.text('0 duplicados');
                $list.html(
                    '<div class="dss-dupfinder-empty">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<h3>Sin duplicados</h3>' +
                    '<p>No se encontraron productos duplicados con los criterios seleccionados.</p>' +
                    '</div>'
                );
                return;
            }

            lastGroups = data.groups;
            currentPage = 1;
            $count.text(data.group_count + ' grupo' + (data.group_count > 1 ? 's' : '') + ' (' + data.total + ' productos)');
            $bulkTrash.show();

            renderPage();
        }).fail(function () {
            $loading.hide();
            $scanBtn.prop('disabled', false);
            alert('Error de conexión.');
        });
    });

    // ── Render current page ──
    function renderPage() {
        var totalPages = Math.ceil(lastGroups.length / perPage);
        var start = (currentPage - 1) * perPage;
        var end = Math.min(start + perPage, lastGroups.length);
        var pageGroups = lastGroups.slice(start, end);

        renderGroups(pageGroups);
        renderPagination(totalPages);
    }

    // ── Render Groups ──
    function renderGroups(groups) {
        $list.empty();

        groups.forEach(function (group) {
            var $group = $('<div class="dss-dup-group"></div>');

            var langCounts = {};
            group.items.forEach(function (item) {
                var l = item.lang || '__no_lang__';
                langCounts[l] = (langCounts[l] || 0) + 1;
            });
            var dupsInGroup = 0;
            for (var l in langCounts) {
                if (langCounts[l] > 1) dupsInGroup += langCounts[l] - 1;
            }

            var $header = $(
                '<div class="dss-dup-group-header">' +
                '<span class="dss-dup-group-reason">' + escHtml(group.reason) + '</span>' +
                '<span class="dss-dup-group-match">' + escHtml(group.match) + '</span>' +
                '<span style="margin-left:auto;font-size:13px;color:#92400e;">' + group.items.length + ' productos' +
                (dupsInGroup > 0 ? ' <span class="dss-dup-lang-warn">(' + dupsInGroup + ' a eliminar)</span>' : '') +
                '</span>' +
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

    // ── Render Pagination ──
    function renderPagination(totalPages) {
        $pagination.empty();

        if (totalPages <= 1) {
            $pagination.hide();
            return;
        }

        $pagination.show();

        var html = '<div class="dss-pag-info">Página ' + currentPage + ' de ' + totalPages + '</div>';
        html += '<div class="dss-pag-buttons">';

        // Previous
        html += '<button type="button" class="button dss-pag-btn" data-page="' + (currentPage - 1) + '"' +
            (currentPage === 1 ? ' disabled' : '') + '>&laquo; Anterior</button>';

        // Page numbers
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            html += '<button type="button" class="button dss-pag-btn" data-page="1">1</button>';
            if (startPage > 2) html += '<span class="dss-pag-dots">...</span>';
        }

        for (var i = startPage; i <= endPage; i++) {
            html += '<button type="button" class="button dss-pag-btn' + (i === currentPage ? ' dss-pag-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += '<span class="dss-pag-dots">...</span>';
            html += '<button type="button" class="button dss-pag-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
        }

        // Next
        html += '<button type="button" class="button dss-pag-btn" data-page="' + (currentPage + 1) + '"' +
            (currentPage === totalPages ? ' disabled' : '') + '>Siguiente &raquo;</button>';

        html += '</div>';

        $pagination.html(html);
    }

    // ── Pagination click ──
    $pagination.on('click', '.dss-pag-btn', function () {
        var page = parseInt($(this).data('page'), 10);
        var totalPages = Math.ceil(lastGroups.length / perPage);
        if (page < 1 || page > totalPages || page === currentPage) return;

        currentPage = page;
        renderPage();

        // Scroll to top of results
        $('html, body').animate({ scrollTop: $results.offset().top - 40 }, 200);
    });

    // ── Trash single product ──
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

    // ── Bulk trash duplicates by language ──
    $bulkTrash.on('click', function () {
        if (lastGroups.length === 0) return;

        var toTrash = 0;
        lastGroups.forEach(function (group) {
            var byLang = {};
            group.items.forEach(function (item) {
                var l = item.lang || '__no_lang__';
                if (!byLang[l]) byLang[l] = [];
                byLang[l].push(item.id);
            });
            for (var l in byLang) {
                if (byLang[l].length > 1) {
                    toTrash += byLang[l].length - 1;
                }
            }
        });

        if (toTrash === 0) {
            alert('No hay duplicados del mismo idioma para eliminar.');
            return;
        }

        if (!confirm('Se moverán ' + toTrash + ' producto(s) duplicados a la papelera.\n\nPor cada grupo, se conservará el producto más antiguo de cada idioma y se eliminarán los demás.\n\n¿Continuar?')) {
            return;
        }

        $bulkTrash.prop('disabled', true).text('Procesando...');

        var groupsData = [];
        lastGroups.forEach(function (group) {
            var items = [];
            group.items.forEach(function (item) {
                items.push({ id: item.id, lang: item.lang || '' });
            });
            groupsData.push({ items: items });
        });

        $.post(dssDupFinder.ajaxUrl, {
            action: 'dss_dupfinder_bulk_trash',
            nonce: dssDupFinder.nonce,
            groups: groupsData
        }, function (res) {
            $bulkTrash.prop('disabled', false).html(
                '<span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Borrar duplicados por idioma'
            );

            if (!res.success) {
                alert('Error: ' + (res.data || 'Desconocido'));
                return;
            }

            var data = res.data;

            // Mark trashed items across all pages in lastGroups
            var trashedSet = {};
            data.trashed.forEach(function (id) { trashedSet[id] = true; });

            lastGroups.forEach(function (group) {
                group.items.forEach(function (item) {
                    if (trashedSet[item.id]) item._trashed = true;
                });
            });

            // Re-render current page to reflect changes
            renderPage();

            // Also mark visible trashed items
            data.trashed.forEach(function (id) {
                var $item = $list.find('.dss-dup-item[data-id="' + id + '"]');
                $item.addClass('trashed');
                $item.find('.dss-dup-status').text('Papelera').attr('class', 'dss-dup-status draft');
            });

            $count.html(
                '<span class="dashicons dashicons-yes" style="color:#16a34a;vertical-align:middle;"></span> ' +
                data.count + ' producto(s) movidos a la papelera'
            );

            if (data.count > 0) {
                $rollback.show();
                $bulkTrash.hide();
            }
        }).fail(function () {
            $bulkTrash.prop('disabled', false).html(
                '<span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Borrar duplicados por idioma'
            );
            alert('Error de conexión.');
        });
    });

    // ── Rollback ──
    $rollback.on('click', function () {
        if (!confirm('¿Restaurar todos los productos eliminados en la última operación masiva?')) {
            return;
        }

        $rollback.prop('disabled', true).text('Restaurando...');

        $.post(dssDupFinder.ajaxUrl, {
            action: 'dss_dupfinder_rollback',
            nonce: dssDupFinder.nonce
        }, function (res) {
            $rollback.prop('disabled', false).html(
                '<span class="dashicons dashicons-undo" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Rollback'
            );

            if (!res.success) {
                alert('Error: ' + (res.data || 'Desconocido'));
                return;
            }

            var data = res.data;

            // Clear trashed flag in data
            var restoredSet = {};
            data.restored.forEach(function (id) { restoredSet[id] = true; });

            lastGroups.forEach(function (group) {
                group.items.forEach(function (item) {
                    if (restoredSet[item.id]) delete item._trashed;
                });
            });

            renderPage();

            data.restored.forEach(function (id) {
                var $item = $list.find('.dss-dup-item[data-id="' + id + '"]');
                $item.removeClass('trashed');
                $item.find('.dss-dup-status').text('Publicado').attr('class', 'dss-dup-status publish');
                $item.find('.dss-btn-trash').prop('disabled', false);
            });

            $count.html(
                '<span class="dashicons dashicons-undo" style="color:#2271b1;vertical-align:middle;"></span> ' +
                data.count + ' producto(s) restaurados correctamente'
            );

            $rollback.hide();
            $bulkTrash.show();
        }).fail(function () {
            $rollback.prop('disabled', false).html(
                '<span class="dashicons dashicons-undo" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Rollback'
            );
            alert('Error de conexión.');
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
