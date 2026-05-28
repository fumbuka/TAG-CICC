<?php

namespace App\Livewire\Users;

use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public ?int $editingUserId = null;

    public string $search = '';

    public string $name = '';

    public string $email = '';

    public string $phone_number = '';

    public string $password = '';

    public ?int $member_id = null;

    /** @var array<int, string> */
    public array $role_names = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($this->editingUserId)],
            'password' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $wasEditing = $this->editingUserId !== null;

        DB::transaction(function () use ($validated): void {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?: null,
                'is_active' => true,
            ];

            if ($validated['password']) {
                $attributes['password'] = Hash::make($validated['password']);
            }

            $user = $this->editingUserId
                ? tap(User::findOrFail($this->editingUserId))->update($attributes)
                : User::create($attributes);

            $user->syncRoles($validated['role_names']);

            Member::query()
                ->where('user_id', $user->id)
                ->when($validated['member_id'], fn ($query) => $query->whereKeyNot($validated['member_id']))
                ->update(['user_id' => null]);

            if ($validated['member_id']) {
                Member::whereKey($validated['member_id'])->update([
                    'user_id' => $user->id,
                ]);
            }
        });

        $this->resetForm();

        $this->dispatch($wasEditing ? 'user-updated' : 'user-created');
    }

    public function edit(int $userId): void
    {
        $user = User::with(['roles', 'member'])->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
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
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
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
            'name',
            'email',
            'phone_number',
            'password',
            'member_id',
            'role_names',
        ]);

        $this->resetErrorBag();
    }
}
