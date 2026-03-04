jQuery(document).ready(function ($) {
  var $uploadBtn = $("#ctb_upload_btn");

  if ($uploadBtn.length) {
    $uploadBtn.click(function (e) {
      e.preventDefault();
      var image = wp
        .media({ title: "Subir Miniatura", multiple: false })
        .open()
        .on("select", function (e) {
          var uploaded_image = image.state().get("selection").first();
          var image_url = uploaded_image.toJSON().url;
          $("#ctb_theme_screenshot").val(image_url);
          $("#ctb_preview").attr("src", image_url).show();
        });
    });
  }
});
