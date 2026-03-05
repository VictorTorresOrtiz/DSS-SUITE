jQuery(document).ready(function ($) {
  $(".ctb_upload_btn").click(function (e) {
    e.preventDefault();
    var $btn = $(this);
    var targetInput = "#" + $btn.data("target");
    var targetPreview = "#" + $btn.data("preview");

    var image = wp
      .media({ title: "Subir Imagen", multiple: false })
      .open()
      .on("select", function () {
        var uploaded_image = image.state().get("selection").first();
        var image_url = uploaded_image.toJSON().url;
        $(targetInput).val(image_url);
        $(targetPreview).attr("src", image_url).show();
      });
  });
});
