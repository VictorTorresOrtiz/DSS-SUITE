jQuery(document).ready(function ($) {
    var $select = $('#dss-sorter-source');
    var $panel = $('#dss-sorter-panel');
    var $list = $('#dss-sortable-list');
    var $count = $('#dss-sorter-count');
    var $saveBtn = $('#dss-sorter-save');
    var $status = $('#dss-sorter-status');
    var currentSource = '';
    var hasChanges = false;

    if (!$select.length) return;

    $select.on('change', function () {
        var source = $(this).val();
        if (!source) {
            $panel.slideUp(200);
            return;
        }
        currentSource = source;
        loadItems(source);
    });

    function loadItems(source) {
        $panel.show();
        $list.html('<li class="dss-sorter-loading"><span class="dashicons dashicons-update" style="animation:rotation 1s infinite linear;"></span> Cargando...</li>');
        $saveBtn.prop('disabled', true);
        $status.text('');
        hasChanges = false;

        $.post(dssSorter.ajaxUrl, {
            action: 'dss_sorter_load',
            nonce: dssSorter.nonce,
            source: source
        }, function (response) {
            if (!response.success) {
                $list.html('<li class="dss-sorter-empty">Error al cargar los elementos.</li>');
                return;
            }

            var data = response.data;
            var items = data.items;

            if (items.length === 0) {
                $list.html('<li class="dss-sorter-empty">No se encontraron elementos para este tipo.</li>');
                $count.text('0 elementos');
                return;
            }

            $count.text(items.length + ' elemento' + (items.length !== 1 ? 's' : ''));
            renderList(items, data.type);
        }).fail(function () {
            $list.html('<li class="dss-sorter-empty">Error de conexión.</li>');
        });
    }

    function renderList(items, type) {
        $list.empty();

        items.forEach(function (item, index) {
            var meta = '';
            if (type === 'tax' && typeof item.count !== 'undefined') {
                meta = '<span class="dss-sort-meta">' + item.count + ' entrada' + (item.count !== 1 ? 's' : '') + '</span>';
            }

            var li = $(
                '<li data-id="' + item.id + '">' +
                '<span class="dss-sort-handle"><span class="dashicons dashicons-menu"></span></span>' +
                '<span class="dss-sort-position">' + (index + 1) + '</span>' +
                '<span class="dss-sort-title">' + escapeHtml(item.title) + '</span>' +
                meta +
                '</li>'
            );
            $list.append(li);
        });

        $list.sortable({
            axis: 'y',
            handle: '.dss-sort-handle',
            placeholder: 'ui-sortable-placeholder',
            tolerance: 'pointer',
            update: function () {
                hasChanges = true;
                $saveBtn.prop('disabled', false);
                $status.text('Cambios sin guardar');
                updatePositions();
            }
        });
    }

    function updatePositions() {
        $list.find('.dss-sort-position').each(function (i) {
            $(this).text(i + 1);
        });
    }

    $saveBtn.on('click', function () {
        if (!hasChanges) return;

        var order = [];
        $list.find('li').each(function () {
            order.push($(this).data('id'));
        });

        $saveBtn.prop('disabled', true);
        $status.text('Guardando...');

        $.post(dssSorter.ajaxUrl, {
            action: 'dss_sorter_save',
            nonce: dssSorter.nonce,
            source: currentSource,
            order: order
        }, function (response) {
            if (response.success) {
                hasChanges = false;
                $status.text('');
                if (window.dssNotify) {
                    window.dssNotify({ title: 'Content Sorter', message: 'Orden guardado correctamente.', type: 'success' });
                }
            } else {
                $saveBtn.prop('disabled', false);
                if (window.dssNotify) {
                    window.dssNotify({ title: 'Content Sorter', message: 'Error al guardar el orden.', type: 'error' });
                }
            }
        }).fail(function () {
            $saveBtn.prop('disabled', false);
            if (window.dssNotify) {
                window.dssNotify({ title: 'Content Sorter', message: 'Error de conexión con el servidor.', type: 'error' });
            }
        });
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
