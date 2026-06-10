import Alpine from 'alpinejs';
import { marked } from 'marked';

window.marked = marked;
window.Alpine = Alpine;

/**
 * panelResizer – reusable mixin for draggable column resizing.
 *
 * Usage (x-data):
 *   Object.assign(myComponent(), panelResizer({ key: 'notes', initial: 320, min: 240, max: 600 }))
 *
 * Also add x-init="initPanelResizer()" on the root element.
 * Bind :style="panelStyle" on the left panel div.
 * Bind @mousedown="startPanelResize($event)" on the drag-handle div.
 */
window.panelResizer = function (opts = {}) {
    const key        = opts.key     || 'panel';
    const min        = opts.min     ?? 240;
    const max        = opts.max     ?? 640;
    const initial    = opts.initial ?? 320;
    const storageKey = 'panel-w-' + key;

    const stored     = parseInt(localStorage.getItem(storageKey), 10);
    const savedWidth = !isNaN(stored) ? Math.max(min, Math.min(max, stored)) : initial;

    return {
        panelWidth:  savedWidth,
        panelMin:    min,
        panelMax:    max,
        panelKey:    storageKey,
        resizing:    false,
        isMobile:    typeof window !== 'undefined' && window.innerWidth < 768,

        initPanelResizer() {
            this.isMobile    = window.innerWidth < 768;
            this._onResize   = () => { this.isMobile = window.innerWidth < 768; };
            window.addEventListener('resize', this._onResize);
        },

        /**
         * Mouse-based drag resize.
         * Sets document cursor during drag so cursor stays correct
         * even when mouse leaves the handle quickly.
         */
        startPanelResize(event) {
            if (this.isMobile) return;
            event.preventDefault();

            this.resizing = true;
            document.body.style.cursor    = 'col-resize';
            document.body.style.userSelect = 'none';

            const startX     = event.clientX;
            const startWidth = this.panelWidth;

            const move = (e) => {
                if (!this.resizing) return;
                const cap = Math.min(this.panelMax, Math.floor(window.innerWidth * 0.65));
                this.panelWidth = Math.max(this.panelMin, Math.min(cap, startWidth + (e.clientX - startX)));
            };

            const up = () => {
                this.resizing = false;
                document.body.style.cursor    = '';
                document.body.style.userSelect = '';
                try { localStorage.setItem(this.panelKey, this.panelWidth); } catch (_) {}
                document.removeEventListener('mousemove', move);
                document.removeEventListener('mouseup',   up);
            };

            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup',   up);
        }
    };
};

Alpine.start();
