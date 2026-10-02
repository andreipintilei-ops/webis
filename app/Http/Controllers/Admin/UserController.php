<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff accounts (admin only). There is no public registration: accounts are
 * created here and the person sets their own password from an emailed link.
 */
class UserController extends Controller
{
    /**
     * GET /admin/utilizatori
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::query()->orderBy('name')->get()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'two_factor' => $user->two_factor_confirmed_at !== null,
                'created_at' => AdminTime::display($user->created_at),
                'is_me' => $user->is($request->user()),
            ])->values()->all(),
            'roles' => array_map(fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()], UserRole::cases()),
        ]);
    }

    /**
     * POST /admin/utilizatori — creates the account with an unusable random
     * password and emails a link to set a real one.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $user = new User([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => Str::password(40),
        ]);
        $user->role = UserRole::from($data['role']);
        $user->email_verified_at = now();
        $user->save();

        Password::sendResetLink(['email' => $user->email]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Contul a fost creat. {$user->email} a primit linkul pentru setarea parolei."]);

        return back();
    }

    /**
     * PUT /admin/utilizatori/{user}
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $role = UserRole::from($data['role']);

        if ($user->isAdmin() && ! $role->isAdmin()) {
            $this->ensureAnotherAdmin($user, 'role');
        }

        $user->name = $data['name'];
        $user->role = $role;
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Utilizatorul a fost salvat.']);

        return back();
    }

    /**
     * DELETE /admin/utilizatori/{user}
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'Nu îți poți șterge propriul cont de aici.']);
        }

        if ($user->isAdmin()) {
            $this->ensureAnotherAdmin($user, 'user');
        }

        $user->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Utilizatorul a fost șters.']);

        return back();
    }

    /**
     * POST /admin/utilizatori/{user}/resetare-parola
     */
    public function sendReset(User $user): RedirectResponse
    {
        Password::sendResetLink(['email' => $user->email]);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Am trimis linkul de resetare la {$user->email}."]);

        return back();
    }

    /**
     * The site must never be left without an administrator.
     */
    private function ensureAnotherAdmin(User $user, string $field): void
    {
        $others = User::query()->where('role', UserRole::Admin->value)->whereKeyNot($user->id)->exists();

        if (! $others) {
            throw ValidationException::withMessages([$field => 'Trebuie să rămână cel puțin un administrator.']);
        }
    }
}
