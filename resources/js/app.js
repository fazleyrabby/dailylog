import Alpine from 'alpinejs';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { THEMES, THEME_MAP, normalizeThemeId, themeFamily } from './themes';

marked.setOptions({
    gfm: true,
    breaks: true,
});

window.marked = marked;

// Render markdown to sanitized HTML. marked does NOT strip dangerous markup,
// so any note body could carry an XSS payload (e.g. <img onerror>, <script>,
// javascript: hrefs). Always pipe parsed output through DOMPurify before it
// reaches x-html. Use window.renderMarkdown(body) instead of marked.parse().
window.renderMarkdown = function (markdown) {
    return DOMPurify.sanitize(marked.parse(markdown || ''), {
        ADD_ATTR: ['target'],
    });
};
window.Alpine = Alpine;

// Expose the theme registry so the inline theme engine (layouts/app.blade.php)
// can normalize ids and resolve a theme's family without importing modules.
window.DailyLogThemes = { list: THEMES, map: THEME_MAP, normalizeThemeId, themeFamily };

// Reactive store backing the theme picker UI and the themed-label helper.
document.addEventListener('alpine:init', () => {
    Alpine.store('themes', {
        list: THEMES,
        map: THEME_MAP,
        current: normalizeThemeId(localStorage.getItem('theme')),
        label(key) {
            const t = THEME_MAP[this.current];
            return (t && t.labels && t.labels[key]) || key;
        },
    });
});

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
    const visibleKey = 'panel-visible-' + key;

    const stored     = parseInt(localStorage.getItem(storageKey), 10);
    const savedWidth = !isNaN(stored) ? Math.max(min, Math.min(max, stored)) : initial;

    return {
        panelWidth:  savedWidth,
        panelMin:    min,
        panelMax:    max,
        panelKey:    storageKey,
        resizing:    false,
        isMobile:    typeof window !== 'undefined' && window.innerWidth < 768,
        showLeftPanel: typeof window !== 'undefined' && (window.innerWidth >= 768 ? localStorage.getItem(visibleKey) !== 'false' : false),

        toggleLeftPanel() {
            this.showLeftPanel = !this.showLeftPanel;
            try { localStorage.setItem(visibleKey, this.showLeftPanel); } catch (_) {}
        },

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

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Markdown } from 'tiptap-markdown';

/**
 * Upload an image File to the backend and return its public URL.
 * Used by both the toolbar picker and the paste/drop handlers.
 */
window.uploadNoteImage = async function (file) {
    const formData = new FormData();
    formData.append('image', file);

    const res = await fetch('/notes/images', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData,
    });

    if (!res.ok) {
        let message = 'Image upload failed';
        try {
            const err = await res.json();
            message = err.message || Object.values(err.errors || {})[0]?.[0] || message;
        } catch (_) {}
        throw new Error(message);
    }

    const data = await res.json();
    return data.url;
};

window.createTiptapEditor = function (element, content, onUpdateCallback) {
    // Upload a dropped/pasted image then insert it at the current selection.
    const insertUploadedImage = (editor, file) => {
        window.uploadNoteImage(file)
            .then((url) => {
                editor.chain().focus().setImage({ src: url }).run();
            })
            .catch((e) => {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: e.message || 'Image upload failed' },
                }));
            });
    };

    return new Editor({
        element: element,
        extensions: [
            StarterKit,
            Image.configure({ inline: false }),
            Markdown.configure({
                transformCopiedText: true,
                transformPastedText: true,
            }),
        ],
        content: content,
        onUpdate({ editor }) {
            const markdown = editor.storage.markdown.getMarkdown();
            onUpdateCallback(markdown);
        },
        editorProps: {
            attributes: {
                class: 'prose prose-sm dark:prose-invert max-w-none focus:outline-none min-h-[400px] h-full text-text-main font-mono text-sm leading-relaxed select-text',
            },
            // Intercept pasted screenshots (clipboard image data).
            handlePaste(view, event) {
                const items = Array.from(event.clipboardData?.items || []);
                const imageItem = items.find((item) => item.type.startsWith('image/'));
                if (!imageItem) {
                    return false;
                }
                const file = imageItem.getAsFile();
                if (file) {
                    event.preventDefault();
                    insertUploadedImage(view.editor, file);
                    return true;
                }
                return false;
            },
            // Intercept dragged-in image files.
            handleDrop(view, event) {
                const file = Array.from(event.dataTransfer?.files || [])
                    .find((f) => f.type.startsWith('image/'));
                if (!file) {
                    return false;
                }
                event.preventDefault();
                insertUploadedImage(view.editor, file);
                return true;
            },
        },
    });
};

Alpine.start();

