<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function start(User $user)
    {
        abort_if($user->role !== 'operator', 404);
        abort_if(!auth()->user()->isAdmin(), 403);

        session(['impersonate_admin_id' => auth()->id()]);
        Auth::login($user);

        return redirect()->route('operator.dashboard');
    }

    public function stop()
    {
        $adminId = session('impersonate_admin_id');
        abort_if(!$adminId, 403);

        session()->forget('impersonate_admin_id');
        Auth::loginUsingId($adminId);

        return redirect()->route('dashboard');
    }
}
