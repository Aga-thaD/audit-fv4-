<?php

namespace App\Policies;

use App\Models\PhoneQC;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PhoneQCPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->pqc_view;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PhoneQC $phoneQC): bool
    {
        return $user->pqc_view;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->pqc_create;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PhoneQC $phoneQC): bool
    {
        return $user->pqc_update;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PhoneQC $phoneQC): bool
    {
        return $user->pqc_delete;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PhoneQC $phoneQC): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PhoneQC $phoneQC): bool
    {
        //
    }
}
