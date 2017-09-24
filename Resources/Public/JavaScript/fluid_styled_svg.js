// replace all svg object tags with inline svg

function injectSvg(target) {
    var xmlDoc = target.contentDocument.documentElement;
    var svg = $(xmlDoc)[0];
    var width = target.getAttribute('width');
    var height = target.getAttribute('height');
    var classNames = target.getAttribute('class').replace('c1-svg__image--inject', 'c1-svg__image--injected');

    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('class', classNames);
    target.parentNode.replaceChild(svg, target);
};

function svgInline() {
    var elements = document.getElementsByClassName("c1-svg__image--inject");
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

(function(fn){var d=document;(d.readyState=='loading')?d.addEventListener('DOMContentLoaded',fn):fn();})(function(){
    svgInline();
});
