<?php

namespace App\Http\Controllers\Admin\Auth;

class LoginController extends \Backpack\CRUD\app\Http\Controllers\Auth\LoginController
{
    /**
     * Extend the application's login form.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showLoginForm()
    {
        $this->data['title'] = trans('backpack::base.login'); // set the page title
        $this->data['username'] = $this->username();

        return view(backpack_view('auth.login'), $this->data);
    }
}
