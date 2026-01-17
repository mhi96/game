<?php
namespace App\Controllers;
use App\Models\UserModel;

class Auth extends BaseController {
    public function login() {
        if ($this->request->getMethod() === 'POST') {
            $m = new UserModel();
            $u = $m->where('email',$this->request->getPost('email'))->first();
            if ($u && password_verify($this->request->getPost('password'),$u['password_hash'])) {
                session()->set('user_id',$u['id']);
                session()->set('username',$u['username']);
                return redirect()->to('/dashboard');
            }
        }
        return view('auth/login');
    }

    public function register() {
        if ($this->request->getMethod() === 'POST') {
                $m = new UserModel();
            $m->insert([
                'username'=>$this->request->getPost('username'),
                'email'=>$this->request->getPost('email'),
                'password_hash'=>password_hash($this->request->getPost('password'),PASSWORD_BCRYPT)
            ]);
            return redirect()->to('/login');
        }
        return view('auth/register');
    }

    public function logout() {
        session()->destroy();
        return redirect()->to('/login');
    }
}
