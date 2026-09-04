<?php

namespace App\Livewire\Settings;

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $user = Auth::user();

        // OAuth-only and passkey-only accounts have a null password, so demanding
        // current_password would lock them out of deleting their own account. They
        // already proved who they are to reach this page.
        if ($user->hasPassword()) {
            $this->validate([
                'password' => ['required', 'string', 'current_password'],
            ]);
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}
