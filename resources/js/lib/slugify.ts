/**
 * A URL slug from Romanian text: "Creare magazin online în Iași" →
 * "creare-magazin-online-in-iasi". Matches the server's slug pattern
 * (lowercase letters, digits, single hyphens).
 */
export function slugify(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 190)
        .replace(/-+$/g, '');
}
