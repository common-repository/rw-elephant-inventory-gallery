jQuery(document).ready(function($){
  $('.rwe-gallery-thumbnails li a').click(function (event) {
    event.preventDefault();
    var href = $(this).attr('href');
    var original = $(this).data('original');
    $('img.rwe-item-photo').attr("src",href);
    $('img.rwe-item-photo').closest("a").attr("href",original);
  });
});