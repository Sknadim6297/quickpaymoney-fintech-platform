<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'blocked'])],
        ]);

        $users = User::query()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('mobile', 'like', '%'.$search.'%');
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('account_status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => [
                'total' => User::count(),
                'active' => User::where('account_status', 'active')->count(),
                'suspended' => User::where('account_status', 'suspended')->count(),
                'blocked' => User::where('account_status', 'blocked')->count(),
            ],
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user,
            'exchanges' => $user->exchangeRequests()->latest()->paginate(10),
            'activity' => AuditLog::with('actor')->where('subject_user_id', $user->id)->latest()->limit(30)->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'account_status' => ['required', Rule::in(['active', 'suspended', 'blocked'])],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);
        abort_if($user->id === $request->user('admin')->id, 403);

        DB::transaction(function () use ($request, $user, $validated): void {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $removingActiveAdmin = $lockedUser->role === 'admin'
                && $lockedUser->account_status === 'active'
                && ($validated['role'] !== 'admin' || $validated['account_status'] !== 'active');

            if ($removingActiveAdmin) {
                $activeAdminIds = User::where('role', 'admin')
                    ->where('account_status', 'active')
                    ->lockForUpdate()
                    ->pluck('id');
                abort_if($activeAdminIds->count() <= 1, 422, 'At least one other active admin is required.');
            }

            $before = ['role' => $lockedUser->role, 'account_status' => $lockedUser->account_status];
            $promotingToAdmin = $validated['role'] === 'admin' && $lockedUser->role !== 'admin';
            $lockedUser->forceFill($validated);
            if ($promotingToAdmin) {
                $lockedUser->totp_enabled = false;
                $lockedUser->totp_secret = null;
                $lockedUser->totp_last_counter = null;
            }
            $lockedUser->save();

            AuditLog::create([
                'actor_user_id' => $request->user('admin')->id,
                'subject_user_id' => $lockedUser->id,
                'event' => 'admin.user_access_updated',
                'metadata' => ['before' => $before, 'after' => $validated],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'User access settings updated.');
    }
}
