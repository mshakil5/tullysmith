<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
  public function dashboard()
  {
    if (Auth::check()) {
      $user = auth()->user();

      if (in_array($user->user_type, [1, 2, 3])) {
        return redirect()->route('admin.dashboard');
      } else if ($user->user_type == '0') {
        return redirect()->route('client.dashboard');
      }
    } else {
      return redirect()->route('login');
    }
  }

  public function adminHome()
  {
    return view('admin.pages.dashboard');
  }

  public function userHome()
  {
    return 'user';
  }
}
