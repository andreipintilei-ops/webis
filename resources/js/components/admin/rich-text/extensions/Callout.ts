import { mergeAttributes, Node } from '@tiptap/core';

export type CalloutVariant = 'info' | 'tip' | 'warning';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        callout: {
            toggleCallout: (variant?: CalloutVariant) => ReturnType;
            setCalloutVariant: (variant: CalloutVariant) => ReturnType;
        };
    }
}

/**
 * A highlighted box around paragraphs: `{type: 'callout', attrs: {variant}}`.
 * Must match App\Support\RichText\Nodes\Callout (variants info, tip, warning).
 */
export const Callout = Node.create({
    name: 'callout',
    group: 'block',
    content: 'block+',
    defining: true,

    addAttributes() {
        return {
            variant: {
                default: 'info',
                parseHTML: (element) =>
                    element.getAttribute('data-variant') ?? 'info',
                renderHTML: (attributes) => ({
                    'data-variant': attributes.variant,
                }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'aside[data-variant]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'aside',
            mergeAttributes(HTMLAttributes, { class: 'rt-callout' }),
            0,
        ];
    },

    addCommands() {
        return {
            toggleCallout:
                (variant = 'info') =>
                ({ commands }) =>
                    commands.toggleWrap(this.name, { variant }),
            setCalloutVariant:
                (variant) =>
                ({ commands }) =>
                    commands.updateAttributes(this.name, { variant }),
        };
    },
});
