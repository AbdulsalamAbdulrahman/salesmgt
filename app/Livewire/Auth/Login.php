<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.guest')]
#[Title('Login')]
class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true], $this->remember)) {
            $user = Auth::user();
            
            // Block attendants from logging in
            if ($user->role === 'attendant') {
                Auth::logout();
                $this->addError('email', 'Attendants are not permitted to access this system. Please contact your Cashier.');
                return;
            }
            
            session()->regenerate();
            
            // Redirect supplier directly to purchase orders
            if ($user->role === 'supplier') {
                return redirect()->route('purchase-orders.index');
            }
            
            return redirect()->intended(route('dashboard'));
        }

        $this->addError('email', 'The provided credentials do not match our records or account is inactive.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
