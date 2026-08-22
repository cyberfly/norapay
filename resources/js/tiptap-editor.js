import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

document.addEventListener('alpine:init', () => {
    Alpine.data('richTextEditor', (initialContent) => {
        // Kept outside the returned object so Alpine never wraps it in a
        // reactive proxy — Tiptap's internal reference checks throw
        // "Applying a mismatched transaction" against a proxied editor.
        let editor = null;

        return {
            isBold: false,
            isItalic: false,
            isStrike: false,
            isBulletList: false,
            isOrderedList: false,
            isBlockquote: false,
            isHeading2: false,
            isHeading3: false,

            init() {
                editor = new Editor({
                    element: this.$refs.element,
                    extensions: [StarterKit],
                    content: initialContent,
                    onUpdate: ({ editor }) => {
                        this.$refs.hidden.value = editor.isEmpty ? '' : editor.getHTML();
                        this.$refs.hidden.dispatchEvent(new Event('input'));
                    },
                    onTransaction: () => this.syncToolbarState(),
                });

                this.syncToolbarState();
            },

            syncToolbarState() {
                this.isBold = editor.isActive('bold');
                this.isItalic = editor.isActive('italic');
                this.isStrike = editor.isActive('strike');
                this.isBulletList = editor.isActive('bulletList');
                this.isOrderedList = editor.isActive('orderedList');
                this.isBlockquote = editor.isActive('blockquote');
                this.isHeading2 = editor.isActive('heading', { level: 2 });
                this.isHeading3 = editor.isActive('heading', { level: 3 });
            },

            toggleBold() {
                editor.chain().focus().toggleBold().run();
            },

            toggleItalic() {
                editor.chain().focus().toggleItalic().run();
            },

            toggleStrike() {
                editor.chain().focus().toggleStrike().run();
            },

            toggleHeading2() {
                editor.chain().focus().toggleHeading({ level: 2 }).run();
            },

            toggleHeading3() {
                editor.chain().focus().toggleHeading({ level: 3 }).run();
            },

            toggleBulletList() {
                editor.chain().focus().toggleBulletList().run();
            },

            toggleOrderedList() {
                editor.chain().focus().toggleOrderedList().run();
            },

            toggleBlockquote() {
                editor.chain().focus().toggleBlockquote().run();
            },

            undo() {
                editor.chain().focus().undo().run();
            },

            redo() {
                editor.chain().focus().redo().run();
            },
        };
    });
});
