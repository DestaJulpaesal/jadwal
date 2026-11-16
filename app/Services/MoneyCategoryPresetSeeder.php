<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MoneyCategoryPresetSeeder
{
    public function seedFor(User $user): void
    {
        if ($user->money_presets_seeded_at !== null) {
            return;
        }

        DB::transaction(function () use ($user) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->money_presets_seeded_at !== null) {
                return;
            }

            foreach (config('presets.money_categories', []) as $type => $names) {
                foreach ($names as $name) {
                    $lockedUser->moneyCategories()->firstOrCreate([
                        'type' => $type,
                        'name' => $name,
                    ]);
                }
            }

            $lockedUser->forceFill(['money_presets_seeded_at' => now()])->save();
            $user->setAttribute('money_presets_seeded_at', $lockedUser->money_presets_seeded_at);
        });
    }
}
