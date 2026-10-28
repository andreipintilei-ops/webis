<?php

namespace App\Http\Middleware;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Support\Brand;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'isAdmin' => $user?->isAdmin() ?? false,
            ],
            // Sidebar badge: quote requests nobody has picked up yet.
            'newLeads' => fn (): int => $user?->role->canAccessAdmin()
                ? Lead::query()->where('status', LeadStatus::New)->count()
                : 0,
            // The square app icon for the admin's logo spots; null = default mark.
            'appIcon' => fn (): ?string => app(Brand::class)->icon()['url'] ?? null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
