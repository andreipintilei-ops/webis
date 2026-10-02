/** A Tiptap document, as stored (see App\Support\RichText\RichText). */
export type TiptapDoc = {
    type: 'doc';
    // `any`, not `unknown`: Inertia's useForm only accepts form-data-shaped
    // values, and nodes nest arbitrarily.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    content: Record<string, any>[];
};

/** One page-builder block as stored: `{id, type, v, data}`. */
export type StoredBlock = {
    id: string;
    type: string;
    v: number;
    // Each block type owns its data shape; its *Editor.vue narrows this.
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    data: Record<string, any>;
};

/** A block type offered by the builder (App\Blocks\BlockRegistry). */
export type BlockType = {
    type: string;
    label: string;
    version: number;
    defaults: Record<string, unknown>;
};

/** A record offered by a picker. `url` is its public path, where it has one. */
export type Choice = { id: number; label: string; url?: string };

/**
 * Records a block or editor can point at, sent by the server with the edit
 * page so pickers work without extra requests.
 */
export type Choices = {
    pages: Choice[];
    servicePages: Choice[];
    projects: Choice[];
    projectCategories: Choice[];
    clients: Choice[];
    testimonials: Choice[];
    posts: Choice[];
    postCategories: Choice[];
};

/** A button: both parts set, or null. */
export type Cta = { label: string; url: string } | null;

/** Per-item SEO overrides (App\Support\Seo\SeoData). */
export type SeoData = {
    title: string | null;
    description: string | null;
    canonical: string | null;
    noindex: boolean;
    og_image_asset_id: number | null;
    focus_keyword: string | null;
};

export type ContentStatus = 'draft' | 'scheduled' | 'published';

export type Option = { value: string; label: string };

export type RevisionSummary = {
    id: number;
    user: string | null;
    created_at: string;
};
