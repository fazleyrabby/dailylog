import Alpine from 'alpinejs';
import { marked } from 'marked';

window.marked = marked;
window.Alpine = Alpine;

window.panelResizer = function (opts = {}) {
    const key = opts.key || 'panel';
    const min = opts.min ?? 240;
    const max = opts.max ?? 640;
    const initial = opts.initial ?? 320;
    const storageKey = 'panel-w-' + key;
    const stored = parseInt(localStorage.getItem(storageKey), 10);
    return {
        panelWidth: !isNaN(stored) ? Math.max(min, Math.min(max, stored)) : initial,
        panelMin: min,
        panelMax: max,
        panelKey: storageKey,
        resizing: false,
        isMobile: typeof window !== 'undefined' && window.innerWidth < 768,
        initPanelResizer() {
            this.isMobile = window.innerWidth < 768;
            this._onResize = () => { this.isMobile = window.innerWidth < 768; };
            window.addEventListener('resize', this._onResize);
        },
        get panelStyle() {
            return this.isMobile ? '' : 'width:' + this.panelWidth + 'px';
        },
        startPanelResize(event) {
            if (this.isMobile) return;
            event.preventDefault();
            this.resizing = true;
            const startX = event.clientX;
            const startWidth = this.panelWidth;
            const move = (e) => {
                if (!this.resizing) return;
                const cap = Math.min(this.panelMax, Math.floor(window.innerWidth * 0.65));
                this.panelWidth = Math.max(this.panelMin, Math.min(cap, startWidth + (e.clientX - startX)));
            };
            const up = () => {
                this.resizing = false;
                try { localStorage.setItem(this.panelKey, this.panelWidth); } catch (_) {}
                document.removeEventListener('mousemove', move);
                document.removeEventListener('mouseup', up);
            };
            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup', up);
        }
    };
};

Alpine.start();
