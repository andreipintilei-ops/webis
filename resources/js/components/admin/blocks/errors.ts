/**
 * Narrow a flat error bag to the keys under `prefix`, with the prefix removed:
 * scoped({ 'items.0.title': 'x' }, 'items.0') → { title: 'x' }.
 *
 * Laravel reports nested errors with dotted keys (`blocks.3.data.items.0.title`);
 * each editor level peels off its own part so fields can look up plain names.
 */
export function scoped(
    errors: Record<string, string>,
    prefix: string,
): Record<string, string> {
    const start = `${prefix}.`;
    const result: Record<string, string> = {};

    for (const [key, message] of Object.entries(errors)) {
        if (key.startsWith(start)) {
            result[key.slice(start.length)] = message;
        }
    }

    return result;
}

/** Whether any error sits at or under `prefix`. */
export function hasErrorsUnder(
    errors: Record<string, string>,
    prefix: string,
): boolean {
    return Object.keys(errors).some(
        (key) => key === prefix || key.startsWith(`${prefix}.`),
    );
}
