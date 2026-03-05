jQuery(document).ready(function ($) {
    var $btn = $('#dss-audit-start');
    var $status = $('#dss-audit-status');
    var $results = $('#dss-audit-results');

    if (!$btn.length) return;

    $btn.on('click', function () {
        $btn.prop('disabled', true).text('Escaneando...');
        $status.text('Analizando páginas publicadas...');
        $results.empty();

        $.post(dssSeoAudit.ajaxUrl, {
            action: 'dss_seo_audit_scan',
            nonce: dssSeoAudit.nonce
        }, function (response) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:middle"></span> Escanear Páginas Publicadas');

            if (!response.success) {
                $status.text('Error al escanear.');
                return;
            }

            var data = response.data;
            var totalIssues = 0;

            data.forEach(function (page) {
                if (!page.error) totalIssues += page.issues.length;
            });

            $status.html(
                '<strong>' + data.length + '</strong> páginas escaneadas &mdash; ' +
                '<strong>' + totalIssues + '</strong> problema' + (totalIssues !== 1 ? 's' : '') + ' encontrado' + (totalIssues !== 1 ? 's' : '')
            );

            data.forEach(function (page) {
                var card = $('<div class="dss-audit-page"></div>');

                var hasIssues = !page.error && page.issues.length > 0;
                var isClean = !page.error && page.issues.length === 0;

                var statusClass = page.error ? 'error' : (hasIssues ? 'warning' : 'success');
                var statusIcon = page.error ? 'dismiss' : (hasIssues ? 'warning' : 'yes-alt');
                var typeLabel = page.type === 'page' ? 'Página' : 'Entrada';

                var header = '<div class="dss-audit-page-header ' + statusClass + '">' +
                    '<span class="dashicons dashicons-' + statusIcon + '"></span> ' +
                    '<strong>' + escapeHtml(page.title) + '</strong>' +
                    '<span class="dss-audit-type-badge">' + typeLabel + '</span>' +
                    '<a href="' + page.url + '" target="_blank" class="dss-audit-link">' +
                    '<span class="dashicons dashicons-external"></span></a>' +
                    '</div>';

                card.append(header);

                if (page.error) {
                    card.append('<div class="dss-audit-page-body"><p class="dss-audit-error">' + page.message + '</p></div>');
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
                                escapeHtml(issue.message) + '</li>'
                            );
                        });
                        body.append(issuesList);
                    }

                    // Heading tree
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
                                escapeHtml(h.text || '(vacío)') +
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
        }).fail(function () {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:middle"></span> Escanear Páginas Publicadas');
            $status.text('Error de conexión.');
        });
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
