// replace all svg img tags with inline svg
function svgInline() {
    $('div.svg-ajaxload').each(function () {
        var imgContainer = $(this);
        var svgUrl = imgContainer.data("src");
        $.get(svgUrl)
                .then(injectSvg);

        function injectSvg(xmlDoc) {
            var svg = $(xmlDoc).find("svg");
            imgContainer.replaceWith(svg);
        }
    });
}

$(function () {
    svgInline();
});