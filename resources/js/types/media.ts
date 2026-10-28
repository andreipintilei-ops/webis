/** A media-library item, as App\Http\Resources\AssetResource serialises it. */
export type Asset = {
    id: number;
    title: string | null;
    alt: string | null;
    caption: string | null;
    width: number | null;
    height: number | null;
    url: string | null;
    thumb_url: string | null;
    file_name: string | null;
    mime_type: string | null;
    size: number | null;
    usages_count?: number;
    created_at: string | null;
};

/** Where an asset is used (App\Support\AdminLinks::describe). */
export type AssetUsage = {
    type: string;
    title: string;
    url: string | null;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
