<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $category->user_id === null;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin() && $category->user_id === null;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin() && $category->user_id === null;
    }

    public function useForTask(User $user, Category $category): bool
    {
        return $category->user_id === null;
    }
}
