jQuery(document).ready(function ($) {
  var $container = $("#dss-public-chat-container");
  var $button = $("#dss-public-chat-button");
  var $window = $("#dss-public-chat-window");
  var $close = $("#dss-public-chat-close");
  var $form = $("#dss-public-chat-form");
  var $history = $("#dss-public-chat-history");
  var $imageUpload = $("#dss-image-upload");
  var $previewContainer = $("#dss-preview-container");
  var $imagePreview = $("#dss-image-preview");
  var currentImage = null;

  // Toggle window
  $button.on("click", function () {
    $window.fadeToggle(300);
  });

  $close.on("click", function () {
    $window.fadeOut(300);
  });

  // Image upload handling
  $imageUpload.on("change", function (e) {
    var file = e.target.files[0];
    if (file && file.type.startsWith("image/")) {
      var reader = new FileReader();
      reader.onload = function (event) {
        $imagePreview.attr("src", event.target.result);
        $previewContainer.show();
        currentImage = file;
      };
      reader.readAsDataURL(file);
    }
  });

  $("#dss-remove-image").on("click", function () {
    currentImage = null;
    $previewContainer.hide();
    $imageUpload.val("");
  });

  // Send message on Enter
  $form.find("textarea").on("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      $form.submit();
    }
  });

  // Send message
  $form.on("submit", function (e) {
    e.preventDefault();
    var message = $form.find("textarea").val().trim();
    if (!message && !currentImage) return;

    appendMessage("user", message, currentImage);
    $form.find("textarea").val("");

    var formData = new FormData();
    formData.append("action", "dss_send_public_chat");
    formData.append("nonce", dssPublicChat.nonce);
    formData.append("message", message);
    if (currentImage) {
      formData.append("image", currentImage);
    }

    // Clean up image after sending
    currentImage = null;
    $previewContainer.hide();
    $imageUpload.val("");

    var $typing = $(
      '<div class="dss-message dss-bot-message dss-typing">Calculando respuesta...</div>',
    );
    $history.append($typing);
    scrollToBottom();

    $.ajax({
      url: dssPublicChat.ajaxUrl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $typing.remove();
        if (response.success) {
          appendMessage("bot", response.data.reply);
        } else {
          appendMessage("bot", "Error: " + response.data.message);
        }
      },
      error: function () {
        $typing.remove();
        appendMessage("bot", "No se pudo conectar con el servidor.");
      },
    });
  });

  // Shortcuts
  $(document).on("click", ".dss-chip", function () {
    var query = $(this).data("query");
    $form.find("textarea").val(query).focus();
  });

  function appendMessage(role, text, imageFile) {
    var $msg = $('<div class="dss-message dss-' + role + '-message"></div>');

    if (imageFile) {
      var $img = $(
        '<img style="max-width: 100%; border-radius: 10px; margin-bottom: 5px;">',
      );
      var reader = new FileReader();
      reader.onload = function (e) {
        $img.attr("src", e.target.result);
      };
      reader.readAsDataURL(imageFile);
      $msg.append($img);
    }

    if (text) {
      $msg.append("<span>" + text + "</span>");
    }

    $history.append($msg);
    scrollToBottom();
  }

  function scrollToBottom() {
    $history.scrollTop($history[0].scrollHeight);
  }
});
