<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class FamilyService
{
    public function create(User $user, string $name): Family
    {
        $family = Family::create([
            'name' => $name,
            'owner_id' => $user->id,
            'invite_code' => Str::random(8),
        ]);

        $family->members()->attach($user->id, ['role' => 'owner']);

        return $family->load('members');
    }

    public function join(User $user, int $familyId, string $inviteCode): Family
    {
        $family = Family::findOrFail($familyId);

        if ($family->invite_code !== $inviteCode) {
            throw new RuntimeException('Invalid invite code.');
        }

        if ($family->members()->where('user_id', $user->id)->exists()) {
            throw new RuntimeException('Already a member of this family.');
        }

        $family->members()->attach($user->id, ['role' => 'member']);

        return $family->load('members');
    }

    public function leave(User $user, int $familyId): void
    {
        $family = Family::findOrFail($familyId);

        if ($family->owner_id === $user->id) {
            throw new RuntimeException('Family owner cannot leave. Transfer ownership or delete the family.');
        }

        $isMember = $family->members()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            throw new RuntimeException('You are not a member of this family.');
        }

        $family->members()->detach($user->id);
    }
}
