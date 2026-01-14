<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login'); // عرض صفحة Blade
    }

    public function login(Request $request)
    { 
        $credentials = $request->only('email', 'password'); 

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
            ]);
        }

        return redirect('/clients'); // نجاح → إعادة توجيه
    }



    public function index()
    {
        $clients = User::all();
        return view('clients', compact('clients'));
    }

    public function toggleStatus($id)
    {
        $client = User::findOrFail($id);
        $client->is_approved = $client->is_approved == 1 ? 0 : 1;
        $client->save();

        return back();
    }

    public function toggleRole($id)
    {
        $client = User::findOrFail($id);
        $client->role = $client->role === 'owner' ? 'tenant' : 'owner';
        $client->save();

        return back();
    }


    public function logout()
{
    Auth::logout();
    return redirect('/login');
}

}

