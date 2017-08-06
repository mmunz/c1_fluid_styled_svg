# fluid-styled-svg

Provides an ImageRenderer for svg. This renders svg images used with the f:media viewhelper.

SVG's up to a configurable size are directly included inline in the HTML. Bigger Graphs
are loaded and injected using javascript. Finally as fallback the svg will be included
in an object tag inside a noscript tag.

```
Configuration

    see constants.txt

```