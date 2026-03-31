<?php

namespace App\Livewire\Dictionaries;

use App\Models\UserDictionary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public UserDictionary $dictionary;

    public function mount(UserDictionary $dictionary): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);
        abort_if($dictionary->user_id !== $user->id, 403);

        $this->dictionary = $dictionary;
    }

    public function render(): View
    {
        return view('livewire.dictionaries.show');
    }
}
