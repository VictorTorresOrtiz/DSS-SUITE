jQuery(document).ready(function ($) {
    var $btn = $('#dss-audit-start');
    var $status = $('#dss-audit-status');
    var $results = $('#dss-audit-results');
    var $progress = $('#dss-audit-progress');
    var $progressFill = $('#dss-audit-progress-fill');
    var $progressText = $('#dss-audit-progress-text');
    var $typeTabs = $('#dss-audit-type-tabs');
    var $pagination = $('#dss-audit-pagination');

    if (!$btn.length) return;

    var allResults = [];
    var totalPages = 0;
    var scannedCount = 0;
    var activeType = '__all__';
    var currentPage = 1;
    var perPage = 10;

    // ── Start scan ──
    $btn.on('click', function () {
        $btn.prop('disabled', true).text('Escaneando...');
        $status.text('');
        $results.empty();
        $typeTabs.hide().empty();
        $pagination.hide();
        $progress.show();
        $progressFill.css('width', '0%');
        $progressText.text('Iniciando escaneo...');

        allResults = [];
        scannedCount = 0;
        totalPages = 0;
        activeType = '__all__';
        currentPage = 1;

        scanBatch(0);
    });

    // ── Batch scanning ──
    function scanBatch(offset) {
        $.post(dssSeoAudit.ajaxUrl, {
            action: 'dss_seo_audit_scan',
            nonce: dssSeoAudit.nonce,
            offset: offset
        }, function (response) {
            if (!response.success) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:middle"></span> Escanear Páginas Publicadas');
                $progress.hide();
                $status.text('Error al escanear.');
                return;
            }

            var data = response.data;

            // Set total on first batch
            if (offset === 0 && data.total > 0) {
                totalPages = data.total;
            }

            // Accumulate results
            allResults = allResults.concat(data.results);
            scannedCount += data.results.length;

            // Update progress
            var pct = totalPages > 0 ? Math.min(Math.round((scannedCount / totalPages) * 100), 100) : 0;
            $progressFill.css('width', pct + '%');
            $progressText.text(scannedCount + ' de ' + totalPages + ' analizadas (' + pct + '%)');

            if (data.has_more) {
                scanBatch(offset + data.batch_size);
            } else {
                onScanComplete();
            }
        }).fail(function () {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:middle"></span> Escanear Páginas Publicadas');
            $progress.hide();
            $status.text('Error de conexión.');
        });
    }

    // ── Scan complete ──
    function onScanComplete() {
        $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:middle"></span> Escanear Páginas Publicadas');
        $progressFill.css('width', '100%');

        var totalIssues = 0;
        allResults.forEach(function (page) {
            if (!page.error) totalIssues += page.issues.length;
        });

        $status.html(
            '<strong>' + allResults.length + '</strong> páginas escaneadas &mdash; ' +
            '<strong>' + totalIssues + '</strong> problema' + (totalIssues !== 1 ? 's' : '') + ' encontrado' + (totalIssues !== 1 ? 's' : '')
        );

        setTimeout(function () { $progress.hide(); }, 500);

        buildTypeTabs();
        currentPage = 1;
        renderCurrentView();
    }

    // ── Build type tabs ──
    function buildTypeTabs() {
        var types = {};
        allResults.forEach(function (page) {
            var t = page.type;
            if (!types[t]) {
                types[t] = { label: page.type_label || t, count: 0, issues: 0 };
            }
            types[t].count++;
            if (!page.error) types[t].issues += page.issues.length;
        });

        var typeKeys = Object.keys(types);
        if (typeKeys.length <= 1 && allResults.length <= perPage) {
            $typeTabs.hide();
            return;
        }

        var html = '<a href="#" class="dss-audit-type-tab active" data-type="__all__">Todos <span class="dss-audit-tab-count">' + allResults.length + '</span></a>';
        typeKeys.forEach(function (t) {
            var issuesBadge = types[t].issues > 0 ? ' <span class="dss-audit-tab-issues">' + types[t].issues + '</span>' : '';
            html += '<a href="#" class="dss-audit-type-tab" data-type="' + escAttr(t) + '">' +
                escHtml(types[t].label) + ' <span class="dss-audit-tab-count">' + types[t].count + '</span>' +
                issuesBadge + '</a>';
        });

        $typeTabs.html(html).show();
    }

    // ── Tab click ──
    $typeTabs.on('click', '.dss-audit-type-tab', function (e) {
        e.preventDefault();
        $typeTabs.find('.dss-audit-type-tab').removeClass('active');
        $(this).addClass('active');
        activeType = $(this).data('type');
        currentPage = 1;
        renderCurrentView();
    });

    // ── Get filtered results ──
    function getFilteredResults() {
        if (activeType === '__all__') return allResults;
        return allResults.filter(function (p) { return p.type === activeType; });
    }

    // ── Render current view ──
    function renderCurrentView() {
        var filtered = getFilteredResults();
        var totalPgs = Math.ceil(filtered.length / perPage);
        var start = (currentPage - 1) * perPage;
        var pageItems = filtered.slice(start, start + perPage);

        renderResults(pageItems);
        renderPagination(totalPgs);
    }

    // ── Render results ──
    function renderResults(pages) {
        $results.empty();

        if (pages.length === 0) {
            $results.html('<p style="padding:20px;color:#64748b;">No hay resultados para este filtro.</p>');
            return;
        }

        pages.forEach(function (page) {
            var card = $('<div class="dss-audit-page"></div>');

            var hasIssues = !page.error && page.issues.length > 0;
            var isClean = !page.error && page.issues.length === 0;

            var statusClass = page.error ? 'error' : (hasIssues ? 'warning' : 'success');
            var statusIcon = page.error ? 'dismiss' : (hasIssues ? 'warning' : 'yes-alt');

            var header = '<div class="dss-audit-page-header ' + statusClass + '">' +
                '<span class="dashicons dashicons-' + statusIcon + '"></span> ' +
                '<strong>' + escHtml(page.title) + '</strong>' +
                '<span class="dss-audit-type-badge">' + escHtml(page.type_label || page.type) + '</span>' +
                '<a href="' + escAttr(page.url) + '" target="_blank" class="dss-audit-link">' +
                '<span class="dashicons dashicons-external"></span></a>' +
                '</div>';

            card.append(header);

            if (page.error) {
                card.append('<div class="dss-audit-page-body"><p class="dss-audit-error">' + escHtml(page.message) + '</p></div>');
            } else {
                var body = $('<div class="dss-audit-page-body"></div>');

                if (page.issues.length > 0) {
                    var issuesList = $('<ul class="dss-audit-issues"></ul>');
                    page.issues.forEach(function (issue) {
                        var cls = issue.type === 'error' ? 'dss-issue-error' : 'dss-issue-warning';
                        var icon = issue.type === 'error' ? 'dismiss' : 'flag';
                        issuesList.append(
                            '<li class="' + cls + '">' +
                            '<span class="dashicons dashicons-' + icon + '"></span> ' +
                            escHtml(issue.message) + '</li>'
                        );
                    });
                    body.append(issuesList);
                }

                if (page.headings.length > 0) {
                    var tree = $('<div class="dss-audit-tree"></div>');
                    tree.append('<p class="dss-audit-tree-title">Estructura de encabezados:</p>');
                    var list = $('<ul class="dss-heading-tree"></ul>');
                    page.headings.forEach(function (h) {
                        var level = parseInt(h.tag.replace('H', ''));
                        var indent = (level - 1) * 20;
                        var tagClass = 'dss-tag-h' + level;
                        list.append(
                            '<li style="padding-left:' + indent + 'px">' +
                            '<span class="dss-heading-tag ' + tagClass + '">' + h.tag + '</span> ' +
                            escHtml(h.text || '(vacio)') +
                            '</li>'
                        );
                    });
                    tree.append(list);
                    body.append(tree);
                }

                if (isClean) {
                    body.append('<p class="dss-audit-clean">Estructura de encabezados correcta.</p>');
                }

                card.append(body);
            }

            $results.append(card);
        });
    }

    // ── Render pagination ──
    function renderPagination(totalPgs) {
        $pagination.empty();

        if (totalPgs <= 1) {
            $pagination.hide();
            return;
        }

        $pagination.show();

        var html = '<div class="dss-pag-info">Página ' + currentPage + ' de ' + totalPgs + '</div>';
        html += '<div class="dss-pag-buttons">';

        html += '<button type="button" class="button dss-pag-btn" data-page="' + (currentPage - 1) + '"' +
            (currentPage === 1 ? ' disabled' : '') + '>&laquo; Anterior</button>';

        var startPg = Math.max(1, currentPage - 2);
        var endPg = Math.min(totalPgs, currentPage + 2);

        if (startPg > 1) {
            html += '<button type="button" class="button dss-pag-btn" data-page="1">1</button>';
            if (startPg > 2) html += '<span class="dss-pag-dots">...</span>';
        }

        for (var i = startPg; i <= endPg; i++) {
            html += '<button type="button" class="button dss-pag-btn' + (i === currentPage ? ' dss-pag-active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }

        if (endPg < totalPgs) {
            if (endPg < totalPgs - 1) html += '<span class="dss-pag-dots">...</span>';
            html += '<button type="button" class="button dss-pag-btn" data-page="' + totalPgs + '">' + totalPgs + '</button>';
        }

        html += '<button type="button" class="button dss-pag-btn" data-page="' + (currentPage + 1) + '"' +
            (currentPage === totalPgs ? ' disabled' : '') + '>Siguiente &raquo;</button>';

        html += '</div>';
        $pagination.html(html);
    }

    // ── Pagination click ──
    $pagination.on('click', '.dss-pag-btn', function () {
        var filtered = getFilteredResults();
        var totalPgs = Math.ceil(filtered.length / perPage);
        var page = parseInt($(this).data('page'), 10);
        if (page < 1 || page > totalPgs || page === currentPage) return;

        currentPage = page;
        renderCurrentView();
        $('html, body').animate({ scrollTop: $typeTabs.offset().top - 40 }, 200);
    });

    // ── Helpers ──
    function escHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
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
});
