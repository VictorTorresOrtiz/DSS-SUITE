jQuery(document).ready(function ($) {
  const $container = $("#dss-chatbox-container");
  const $button = $("#dss-chatbox-button");
  const $closeBtn = $("#dss-chatbox-close");
  const $history = $("#dss-chatbox-history");
  const $form = $("#dss-chatbox-form");
  const $textarea = $form.find("textarea");

  // Toggle Chat Window
  $button.on("click", function () {
    $container.toggleClass("dss-chatbox-active");
    scrollToBottom();
  });

  $closeBtn.on("click", function () {
    $container.removeClass("dss-chatbox-active");
  });

  function scrollToBottom() {
    $history.animate({ scrollTop: $history.prop("scrollHeight") }, 300);
  }

  function addMessage(text, role) {
    const messageClass =
      role === "user" ? "dss-user-message" : "dss-bot-message";
    const $msg = $('<div class="dss-message ' + messageClass + '"></div>').text(
      text,
    );
    $history.append($msg);
    scrollToBottom();
  }

  function showTypingIndicator() {
    const $indicator = $(
      '<div id="dss-typing" class="dss-message dss-bot-message"><div class="dss-typing-indicator"><span></span><span></span><span></span></div></div>',
    );
    $history.append($indicator);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    $("#dss-typing").remove();
  }

  // Handle Form Submission
  $form.on("submit", function (e) {
    e.preventDefault();

    const message = $textarea.val().trim();
    if (!message) return;

    // Reset and add user message
    $textarea.val("");
    addMessage(message, "user");

    // Show typing indicator
    showTypingIndicator();

    const formData = {
      action: "dss_send_chatbox_inquiry",
      nonce: dssChatbox.nonce,
      chat_message: message,
    };

    $.post(dssChatbox.ajaxUrl, formData, function (res) {
      removeTypingIndicator();
      if (res.success) {
        addMessage(res.data.reply, "bot");
      } else {
        addMessage(res.data.message || "Ocurrió un error inesperado.", "bot");
      }
    }).fail(function () {
      removeTypingIndicator();
      addMessage(
        "No pude conectar con el servidor. Por favor, revisa tu conexión.",
        "bot",
      );
    });
  });

  // Handle Suggestion Chips
  $(".dss-chip").on("click", function () {
    const query = $(this).data("query");
    $textarea.val(query).focus();

    // Si la consulta es una pregunta directa, enviar automáticamente
    if (query.endsWith("?")) {
      $form.trigger("submit");
    }
  });

  // Submit on Enter (but new line on Shift+Enter)
  $textarea.on("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      $form.trigger("submit");
    }
  });
});
