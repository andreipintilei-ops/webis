import { http } from '@inertiajs/vue3';

type Method = 'get' | 'post' | 'put' | 'patch' | 'delete';

/**
 * A failed JSON request. `errors` holds Laravel's per-field validation
 * messages (first message per field) when the status is 422.
 */
export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors: Record<string, string> = {},
    ) {
        super(message);
    }
}

type RequestOptions = {
    onUploadProgress?: (percent: number) => void;
};

/**
 * JSON calls from admin components that must not trigger a page visit — the
 * media library, the picker, lookups. Goes through Inertia's HTTP client, so
 * the XSRF token and the XHR header are sent the same way as page visits.
 */
export async function api<T>(
    method: Method,
    url: string,
    data?: unknown,
    options: RequestOptions = {},
): Promise<T> {
    try {
        const response = await http.getClient().request({
            method,
            url,
            data,
            headers: { Accept: 'application/json' },
            onUploadProgress: options.onUploadProgress
                ? (event) =>
                      options.onUploadProgress?.(
                          event.total
                              ? Math.round((event.loaded / event.total) * 100)
                              : 0,
                      )
                : undefined,
        });

        return (response.data ? JSON.parse(response.data) : undefined) as T;
    } catch (error) {
        throw toApiError(error);
    }
}

function toApiError(error: unknown): ApiError {
    const response = (
        error as { response?: { status: number; data: string } } | null
    )?.response;

    if (!response) {
        return new ApiError(
            'Conexiunea a eșuat. Verifică internetul și încearcă din nou.',
            0,
        );
    }

    let body: { message?: string; errors?: Record<string, string[]> } = {};

    try {
        body = JSON.parse(response.data);
    } catch {
        // Not JSON (e.g. an HTML error page) — fall through to a generic message.
    }

    const errors = Object.fromEntries(
        Object.entries(body.errors ?? {}).map(([field, messages]) => [
            field,
            messages[0] ?? '',
        ]),
    );

    return new ApiError(
        body.message ?? `Cererea a eșuat (${response.status}).`,
        response.status,
        errors,
    );
}
