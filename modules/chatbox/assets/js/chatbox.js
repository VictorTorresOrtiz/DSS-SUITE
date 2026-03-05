jQuery(document).ready(function ($) {
  const $container = $("#dss-chatbox-container");
  const $button = $("#dss-chatbox-button");
  const $closeBtn = $("#dss-chatbox-close");
  const $form = $("#dss-chatbox-form");
  const $response = $("#dss-chatbox-response");

  // Toggle Chat Window
  $button.on("click", function () {
    $container.toggleClass("dss-chatbox-active");
  });

  $closeBtn.on("click", function () {
    $container.removeClass("dss-chatbox-active");
  });

  // Close on escape key
  $(document).on("keyup", function (e) {
    if (e.key === "Escape") {
      $container.removeClass("dss-chatbox-active");
    }
  });

  // Handle Form Submission
  $form.on("submit", function (e) {
    e.preventDefault();

    const $submitBtn = $form.find('button[type="submit"]');
    const originalText = $submitBtn.text();

    // Show loading state
    $submitBtn.prop("disabled", true).text("Enviando...");
    $response.hide().removeClass("dss-response-success dss-response-error");

    const formData = {
      action: "dss_send_chatbox_inquiry",
      nonce: dssChatbox.nonce,
      chat_name: $form.find('input[name="chat_name"]').val(),
      chat_message: $form.find('textarea[name="chat_message"]').val(),
    };

    $.post(dssChatbox.ajaxUrl, formData, function (res) {
      if (res.success) {
        $response
          .text(res.data.message)
          .addClass("dss-response-success")
          .fadeIn();
        $form.trigger("reset");

        // Close after a short delay
        setTimeout(function () {
          $container.removeClass("dss-chatbox-active");
          $response.hide();
        }, 4000);
      } else {
        $response
          .text(res.data.message)
          .addClass("dss-response-error")
          .fadeIn();
      }
    })
      .fail(function () {
        $response
          .text(
            "Ocurrió un error al enviar la consulta. Por favor, inténtalo de nuevo.",
          )
          .addClass("dss-response-error")
          .fadeIn();
      })
      .always(function () {
        $submitBtn.prop("disabled", false).text(originalText);
      });
  });
});
