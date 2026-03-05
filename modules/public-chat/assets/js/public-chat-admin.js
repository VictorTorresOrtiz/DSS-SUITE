jQuery(document).ready(function ($) {
  // Add Shortcut
  $("#dss-add-shortcut").on("click", function () {
    var index = $(".dss-shortcut-card").length;
    var html = `
            <div class="dss-shortcut-card">
                <button type="button" class="dss-remove-shortcut">&times;</button>
                <input type="text" name="dss_public_chat_shortcuts[${index}][label]" placeholder="Título (ej: Hola)">
                <input type="text" name="dss_public_chat_shortcuts[${index}][query]" placeholder="Prompt (ej: Hola, ¿cómo estás?)">
            </div>
        `;
    $(".dss-shortcuts-grid").append(html);
  });

  // Remove Shortcut
  $(document).on("click", ".dss-remove-shortcut", function () {
    $(this)
      .closest(".dss-shortcut-card")
      .fadeOut(200, function () {
        $(this).remove();
        // Re-index to ensure array consistency on save
        $(".dss-shortcut-card").each(function (i) {
          $(this)
            .find('input[name*="label"]')
            .attr("name", `dss_public_chat_shortcuts[${i}][label]`);
          $(this)
            .find('input[name*="query"]')
            .attr("name", `dss_public_chat_shortcuts[${i}][query]`);
        });
      });
  });

  // Media Uploader for Logo
  $("#dss-select-logo").on("click", function (e) {
    e.preventDefault();
    var frame = wp.media({
      title: "Seleccionar Logo del Chat",
      button: { text: "Usar este logo" },
      multiple: false,
    });
    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      $("#dss_public_chat_logo").val(attachment.url);
      $("#dss-logo-preview").attr("src", attachment.url);
    });
    frame.open();
  });
});
