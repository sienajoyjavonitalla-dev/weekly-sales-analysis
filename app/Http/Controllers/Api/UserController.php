<?php

namespace App\Http\Controllers\Api;

use App\Domain\WeeklyAnalysis\Security\Services\AuditLogger;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'email',
                'role',
                'is_active',
                'created_at',
                'updated_at',
            ]);

        return response()->json(['data' => $users]);
    }

    public function store(StoreUserRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('create', User::class);

        $payload = $request->validated();
        $payload['is_active'] = $payload['is_active'] ?? true;

        $user = User::query()->create($payload);
        $auditLogger->log('user.created', $request, $user);

        return response()->json(['data' => $user->refresh()], 201);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('update', $user);

        $payload = $request->validated();

        if ($request->user()->is($user)) {
            if (array_key_exists('is_active', $payload) && ! $payload['is_active']) {
                throw ValidationException::withMessages([
                    'is_active' => ['You cannot deactivate your own account.'],
                ]);
            }

            if (array_key_exists('role', $payload) && $payload['role'] !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => ['You cannot demote your own account.'],
                ]);
            }
        }

        if (array_key_exists('password', $payload) && ($payload['password'] === null || $payload['password'] === '')) {
            unset($payload['password']);
        }

        $user->update($payload);
        $auditLogger->log('user.updated', $request, $user);

        return response()->json(['data' => $user->refresh()]);
    }
}
