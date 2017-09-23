// replace all svg img tags with inline svg
function svgInline() {
    $('div.svg-ajaxload').each(function () {
        var imgContainer = $(this);
        var object = $(this).children('object').first();
        var width = object.attr('width');
        var height = object.attr('height');
        var svgUrl = imgContainer.data("src");
        $.get(svgUrl)
                .then(injectSvg);

        function injectSvg(xmlDoc) {
            var svg = $(xmlDoc).find("svg");
            if (parseInt(width) && parseInt(height)) {
                svg.attr('width', width);
                svg.attr('height', height);
            };
            imgContainer.replaceWith(svg);
        }
    });
}

$(function () {
    svgInline();
});