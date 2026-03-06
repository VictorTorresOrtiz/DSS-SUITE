(function ($) {
    'use strict';

    var $container = $('#dss-public-chat-container');
    if (!$container.length || $container.data('mode') !== 'course-advisor') return;

    var $history = $('#dss-public-chat-history');
    var $form = $('#dss-ca-form');
    var $input = $form.find('textarea');
    var $sendBtn = $form.find('.dss-ca-send-btn');
    var conversationHistory = [];

    // ── Quick question chips ──
    $history.on('click', '.dss-ca-chip', function () {
        var query = $(this).data('query');
        $input.val(query);
        $form.trigger('submit');
        $(this).closest('.dss-ca-chips').remove();
    });

    // ── Submit form ──
    $form.on('submit', function (e) {
        e.preventDefault();

        var message = $.trim($input.val());
        if (!message) return;

        // Add user message
        appendMessage('user', message);
        conversationHistory.push({ role: 'user', text: message });
        $input.val('');
        $sendBtn.prop('disabled', true);

        // Show typing indicator
        var $typing = $('<div class="dss-message dss-bot-message dss-typing"><span></span><span></span><span></span></div>');
        $history.append($typing);
        scrollToBottom();

        $.ajax({
            url: dssCourseAdvisor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dss_course_advisor',
                nonce: dssCourseAdvisor.nonce,
                message: message,
                history: conversationHistory.slice(-10) // Last 10 messages for context
            },
            success: function (res) {
                $typing.remove();
                $sendBtn.prop('disabled', false);

                if (!res.success) {
                    appendMessage('bot', res.data.message || 'Error al procesar la consulta.');
                    return;
                }

                var data = res.data;

                // Render reply with markdown-like formatting
                appendMessage('bot', formatReply(data.reply));
                conversationHistory.push({ role: 'model', text: data.reply });

                // Render course cards if any
                if (data.courses && data.courses.length > 0) {
                    var $cards = $('<div class="dss-ca-course-cards"></div>');
                    data.courses.forEach(function (course) {
                        var priceStr = course.price ? course.price + ' &euro;' : 'Consultar';
                        var cats = course.categories && course.categories.length > 0
                            ? '<span class="dss-ca-card-cats">' + escHtml(course.categories.join(', ')) + '</span>'
                            : '';

                        var imgHtml = course.image
                            ? '<img src="' + escAttr(course.image) + '" alt="" class="dss-ca-card-img">'
                            : '<div class="dss-ca-card-img-placeholder"><span class="dashicons dashicons-welcome-learn-more"></span></div>';

                        $cards.append(
                            '<a href="' + escAttr(course.url) + '" target="_blank" class="dss-ca-course-card">' +
                            imgHtml +
                            '<div class="dss-ca-card-info">' +
                            '<span class="dss-ca-card-name">' + escHtml(course.name) + '</span>' +
                            cats +
                            '<span class="dss-ca-card-price">' + priceStr + '</span>' +
                            '</div>' +
                            '<span class="dashicons dashicons-arrow-right-alt2 dss-ca-card-arrow"></span>' +
                            '</a>'
                        );
                    });
                    $history.append($cards);
                    scrollToBottom();
                }
            },
            error: function () {
                $typing.remove();
                $sendBtn.prop('disabled', false);
                appendMessage('bot', 'Error de conexion. Intentalo de nuevo.');
            }
        });
    });

    // ── Enable/disable send button ──
    $input.on('input', function () {
        $sendBtn.prop('disabled', !$.trim($(this).val()));
    });

    // ── Enter to send ──
    $input.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $form.trigger('submit');
        }
    });

    // ── Helpers ──
    function appendMessage(type, html) {
        var cls = type === 'user' ? 'dss-user-message' : 'dss-bot-message';
        $history.append('<div class="dss-message ' + cls + '">' + html + '</div>');
        scrollToBottom();
    }

    function scrollToBottom() {
        $history.scrollTop($history[0].scrollHeight);
    }

    function formatReply(text) {
        // Bold
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        // Line breaks
        text = text.replace(/\n/g, '<br>');
        return text;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})(jQuery);
