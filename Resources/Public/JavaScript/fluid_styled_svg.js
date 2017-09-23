// replace all svg object tags with inline svg

function injectSvg(target) {
    console.log("inject svg");
    var xmlDoc = target.contentDocument.documentElement;
    var width = target.getAttribute('width');
    var height = target.getAttribute('height');
    var aspectRatio = (height / width) * 100;

    var svg = $(xmlDoc)[0];
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');

    var svgAndRatiobox = document.createElement("div");
    svgAndRatiobox.setAttribute('class', 'svg-ratiobox');
    svgAndRatiobox.setAttribute('style', 'padding-bottom:'+ aspectRatio +'%;width:' + width + 'px');
    svgAndRatiobox.appendChild(svg);
    target.parentNode.replaceChild(svgAndRatiobox, target);
};

function svgInline() {
    var elements = document.getElementsByClassName("svg-ajaxload");
    for(var i=0; i<elements.length; i++)
    {
        var el = elements[i];
        el.addEventListener('load', function () {
            injectSvg(el)
        }, true);
        // next line triggers load event in case the resource was already loaded from cache
        // fixes an issue in safari
        el.data = el.data;
    }
}

$(function () {
   svgInline();
});