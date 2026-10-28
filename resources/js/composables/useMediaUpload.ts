import { reactive } from 'vue';
import { api, ApiError } from '@/lib/adminApi';
import { store } from '@/routes/admin/media';
import type { Asset } from '@/types/media';

export type UploadItem = {
    key: string;
    name: string;
    progress: number;
    status: 'queued' | 'uploading' | 'done' | 'failed';
    error: string | null;
    asset: Asset | null;
};

const MAX_BYTES = 15 * 1024 * 1024;

/**
 * Uploads files one at a time, each with its own progress and error, so one
 * bad file never sinks the batch. `onUploaded` fires per finished asset.
 */
export function useMediaUpload(onUploaded?: (asset: Asset) => void) {
    const items = reactive<UploadItem[]>([]);
    const queue = new Map<string, File>();
    let running = false;

    function add(files: FileList | File[]): void {
        for (const file of Array.from(files)) {
            items.push({
                key: `${file.name}-${file.size}-${Math.random()}`,
                name: file.name,
                progress: 0,
                status: file.size > MAX_BYTES ? 'failed' : 'queued',
                error:
                    file.size > MAX_BYTES ? 'Fișierul depășește 15 MB.' : null,
                asset: null,
            });
            queue.set(items[items.length - 1].key, file);
        }

        void run();
    }

    async function run(): Promise<void> {
        if (running) {
            return;
        }

        running = true;

        for (const item of items) {
            if (item.status !== 'queued') {
                continue;
            }

            const file = queue.get(item.key);

            if (!file) {
                continue;
            }

            item.status = 'uploading';

            const data = new FormData();
            data.append('file', file);

            try {
                const response = await api<{ asset: Asset }>(
                    'post',
                    store.url(),
                    data,
                    {
                        onUploadProgress: (percent) =>
                            (item.progress = percent),
                    },
                );

                item.status = 'done';
                item.progress = 100;
                item.asset = response.asset;
                onUploaded?.(response.asset);
            } catch (error) {
                item.status = 'failed';
                item.error =
                    error instanceof ApiError
                        ? (error.errors.file ?? error.message)
                        : 'Încărcarea a eșuat.';
            } finally {
                queue.delete(item.key);
            }
        }

        running = false;

        // Files added while the last one was uploading.
        if (items.some((item) => item.status === 'queued')) {
            void run();
        }
    }

    function clearFinished(): void {
        for (let i = items.length - 1; i >= 0; i--) {
            if (items[i].status === 'done' || items[i].status === 'failed') {
                items.splice(i, 1);
            }
        }
    }

    return { items, add, clearFinished };
}
