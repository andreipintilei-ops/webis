/**
 * Every string inside a block or Tiptap structure, in order — a rough plain
 * text of the content, used for the focus-keyword check. Ids, types and URLs
 * are skipped.
 */
export function contentText(value: unknown): string {
    const parts: string[] = [];

    const walk = (node: unknown, key: string | null): void => {
        if (typeof node === 'string') {
            if (
                !['id', 'type', 'url', 'icon', 'source', 'layout'].includes(
                    key ?? '',
                )
            ) {
                parts.push(node);
            }
        } else if (Array.isArray(node)) {
            node.forEach((item) => walk(item, key));
        } else if (node && typeof node === 'object') {
            for (const [childKey, child] of Object.entries(node)) {
                walk(child, childKey);
            }
        }
    };

    walk(value, null);

    return parts.join(' ');
}
