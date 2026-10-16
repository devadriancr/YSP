<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Users of the portal and the vendors each one can see. Admins only (see routes).
 */
class UserController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('buscar'));

        $users = User::query()
            ->with('vendors')
            // Name, email, vendor name or exact vendor number; whereLike ignores case on every database
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")
                ->orWhereHas('vendors', fn ($vendors) => $vendors
                    ->whereLike('name', "%{$search}%")
                    ->when(ctype_digit($search), fn ($vendors) => $vendors->orWhere('number', (int) $search)))))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.users.index', [
            'title' => 'Usuarios',
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('pages.users.form', [
            'title' => 'Nuevo usuario',
            'user' => tap(new User, fn (User $user) => $user->role = User::SUPPLIER), // role is not fillable
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            $user->role = $request->validated('role');
            $user->email_verified_at = now(); // created by an admin, not by sign up
            $user->save();
            $user->vendors()->sync($this->assignedVendors($request));

            return $user;
        });

        return redirect()->route('users.index')->with('status', __('Usuario :name creado.', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        return view('pages.users.form', [
            'title' => 'Editar usuario',
            'user' => $user->load('vendors'),
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['name', 'email']));
            $user->role = $request->validated('role');
            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }
            $user->save();
            $user->vendors()->sync($this->assignedVendors($request));
        });

        return redirect()->route('users.index')->with('status', __('Usuario :name actualizado.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('No puedes eliminar tu propio usuario.'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', __('Usuario :name eliminado.', ['name' => $user->name]));
    }

    /**
     * Only suppliers have vendors assigned; planners and admins already see all of them.
     *
     * @return array<int, int>
     */
    private function assignedVendors(UserRequest $request): array
    {
        return $request->validated('role') === User::SUPPLIER ? $request->validated('vendors', []) : [];
    }

    /**
     * @return array<int, array{id: int, number: int, name: string}>
     */
    private function vendorOptions(): array
    {
        return Vendor::query()
            ->orderBy('number')
            ->get(['id', 'number', 'name'])
            ->map(fn (Vendor $vendor) => ['id' => $vendor->id, 'number' => $vendor->number, 'name' => $vendor->name])
            ->all();
    }
}
