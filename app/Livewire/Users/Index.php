<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\ChecksSeededPermissions;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Index extends Component
{
    use ChecksSeededPermissions;
    use WithPagination;

    public string $section = 'list';

    public ?int $editingUserId = null;

    public string $search = '';

    public string $email = '';

    public string $phone_number = '';

    public string $password = '';

    public ?int $member_id = null;

    /** @var array<int, string> */
    public array $role_names = [];

    public string $selected_role_name = '';

    /** @var array<int, string> */
    public array $role_permission_names = [];

    public function mount(?string $section = null): void
    {
        $this->section = $section ?: 'list';

        abort_unless(match ($this->section) {
            'access' => $this->canManageUserAccess(),
            'role-matrix' => $this->canManageRoleMatrix(),
            default => $this->canViewUsers(),
        }, 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMemberId(): void
    {
        if (! $this->member_id) {
            $this->editingUserId = null;
            $this->email = '';
            $this->phone_number = '';
            $this->password = '';
            $this->role_names = [];

            return;
        }

        $member = Member::with(['user.roles'])->find($this->member_id);

        if (! $member) {
            return;
        }

        $this->editingUserId = $member->user?->id;
        $this->email = $member->user?->email ?? $member->email ?? '';
        $this->phone_number = $member->user?->phone_number ?? $member->phone_number ?? '';
        $this->password = '';
        $this->role_names = $member->user?->roles->pluck('name')->all() ?? [];
    }

    public function save(): void
    {
        abort_unless($this->canManageUserAccess(), 403);

        $validated = $this->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($this->editingUserId)],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $member = Member::with('user')->findOrFail($validated['member_id']);
        $wasEditing = $this->editingUserId !== null || $member->user !== null;

        DB::transaction(function () use ($member, $validated): void {
            $fullName = collect([$member->first_name, $member->middle_name, $member->last_name])
                ->filter()
                ->join(' ');
            $user = $this->editingUserId
                ? User::findOrFail($this->editingUserId)
                : $member->user;

            $attributes = [
                'name' => $fullName,
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?: null,
                'is_active' => true,
            ];

            if ($validated['password']) {
                $attributes['password'] = Hash::make($validated['password']);
            }

            if ($user) {
                $user->update($attributes);
            } else {
                $user = User::create($attributes);
            }

            $user->syncRoles($validated['role_names']);

            Member::query()
                ->where('user_id', $user->id)
                ->whereKeyNot($member->id)
                ->update(['user_id' => null]);

            $member->update([
                'user_id' => $user->id,
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?: $member->phone_number,
            ]);
        });

        $this->resetForm();

        $this->dispatch($wasEditing ? 'user-updated' : 'user-created');
    }

    public function updatedSelectedRoleName(): void
    {
        $role = $this->selected_role_name !== ''
            ? Role::query()->where('name', $this->selected_role_name)->first()
            : null;

        $this->role_permission_names = $role?->permissions->pluck('name')->all() ?? [];
    }

    public function saveRoleMatrix(): void
    {
        abort_unless($this->canManageRoleMatrix(), 403);

        $validated = $this->validate([
            'selected_role_name' => ['required', 'string', Rule::exists('roles', 'name')],
            'role_permission_names' => ['array'],
            'role_permission_names.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        Role::query()
            ->where('name', $validated['selected_role_name'])
            ->firstOrFail()
            ->syncPermissions($validated['role_permission_names']);

        $this->dispatch('role-matrix-updated');
    }

    public function edit(int $userId): void
    {
        abort_unless($this->canManageUserAccess(), 403);

        $user = User::with(['roles', 'member'])->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->section = 'access';
        $this->email = $user->email;
        $this->phone_number = $user->phone_number ?? '';
        $this->member_id = $user->member?->id;
        $this->role_names = $user->roles->pluck('name')->all();
        $this->password = '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggleActive(int $userId): void
    {
        abort_unless($this->canManageUserAccess(), 403);

        if ($userId === Auth::id()) {
            $this->addError('user_action', __('messages.user_cannot_deactivate_self'));

            return;
        }

        $user = User::findOrFail($userId);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $this->dispatch('user-updated');
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['roles', 'member'])
            ->whereHas('member')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%")
                        ->orWhereHas('member', function ($query): void {
                            $query
                                ->where('first_name', 'like', "%{$this->search}%")
                                ->orWhere('middle_name', 'like', "%{$this->search}%")
                                ->orWhere('last_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.users.index', [
            'section' => $this->section,
            'canManageUserAccess' => $this->canManageUserAccess(),
            'canManageRoleMatrix' => $this->canManageRoleMatrix(),
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'permissionGroups' => Permission::query()
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Permission $permission): string => Str::of($permission->name)->before('.')->headline()->toString()),
            'members' => Member::query()
                ->with('user')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingUserId',
            'email',
            'phone_number',
            'password',
            'member_id',
            'role_names',
        ]);

        $this->resetErrorBag();
    }

    private function canViewUsers(): bool
    {
        return $this->permissionsAreUnseeded()
            || Auth::user()?->can('users.manage')
            || Auth::user()?->can('users.list');
    }

    private function canManageUserAccess(): bool
    {
        return $this->permissionsAreUnseeded()
            || Auth::user()?->can('users.manage')
            || Auth::user()?->can('users.access');
    }

    private function canManageRoleMatrix(): bool
    {
        return $this->permissionsAreUnseeded() || (Auth::user()?->can('users.role-matrix') ?? false);
    }
}
