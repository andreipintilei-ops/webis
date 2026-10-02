import { Node } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import AssetImageView from '../AssetImageView.vue';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        assetImage: {
            insertAssetImage: (attrs: {
                assetId: number;
                caption?: string | null;
            }) => ReturnType;
        };
    }
}

/**
 * A media-library image inside rich text: `{type: 'assetImage', attrs:
 * {assetId, caption}}`. Must match App\Support\RichText\Nodes\AssetImage —
 * the server renders it and drops it when `assetId` is missing.
 */
export const AssetImage = Node.create({
    name: 'assetImage',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
        return {
            assetId: { default: null, rendered: false },
            caption: { default: null, rendered: false },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'figure[data-asset-id]',
                getAttrs: (element) => ({
                    assetId: Number(
                        (element as HTMLElement).dataset.assetId ?? 0,
                    ),
                    caption:
                        element.querySelector('figcaption')?.textContent ??
                        null,
                }),
            },
        ];
    },

    renderHTML({ node }) {
        return ['figure', { 'data-asset-id': String(node.attrs.assetId) }];
    },

    addNodeView() {
        return VueNodeViewRenderer(AssetImageView);
    },

    addCommands() {
        return {
            insertAssetImage:
                (attrs) =>
                ({ commands }) =>
                    commands.insertContent({ type: this.name, attrs }),
        };
    },
});
