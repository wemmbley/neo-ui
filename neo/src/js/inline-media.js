class InlineMedia {
    constructor() {
        this.mediaNodes = [];
        this.mediaNodesToRemove = [];

        this.walkMediaNodes(document.body);
        this.removeMediaNodes(this.mediaNodesToRemove);
    }

    walkMediaNodes(n) {
        for (let c of n.childNodes) {
            if (c.nodeType === 3) { // text node
                const val = c.nodeValue.trim();
                if (val.startsWith("@media")) {
                    this.mediaNodes.push([val, c.nextSibling]);
                    this.mediaNodesToRemove.push(c);
                }
            } else {
                if (c.hasChildNodes()) {
                    this.walkMediaNodes(c);
                }
            }
        }
    }

    removeMediaNodes(n) {
        n.forEach((el) => el.remove());
    }
}

module.exports = InlineMedia;