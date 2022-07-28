<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; 
use Image;

/**
 * Class AccountController.
 */
class AccountController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $fileName = Auth::user()->profile_pic;
   

        return view('frontend.user.account', compact('fileName'));
    }

    public function uploadAvatar(Request $request) 
    {   
        request()->validate([
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $avatar = $request->file('profile_pic');
        $fileName = time() . '.' . $avatar->extension();

        $image = Image::make($avatar);

        $image->resize(60, 60, function ($constraint) {
            $constraint->aspectRatio();
        });

        $image->save(public_path('uploads/resiz_' . $fileName));

        
        // $image = $avatar->move(public_path('uploads'), $fileName);

        $userLog = Auth::user()->id;
        
            $avatarDb = User::findOrFail($userLog);
            // dd($avatarDb->profile_pic);

            $avatarDb['profile_pic'] = 'resiz_'.$fileName;
            $avatarDb->update();

            



            return redirect()->route('frontend.user.account');
        // return view('frontend.user.account', compact('fileName'));
    }
	
	public function store(Request $request) 
    {   
            if($request->email){

                $name = explode('@', $request->email);

                $data = [
                    'email' => $request->email,
                    'password' => str_random(12),
                    'name' => $name[0],
                    'type' => 'admin'
                ];

                User::create($data);

                return response()->json([
                    'error' => false,
                    'data' => 'Utente '.$data['name'].' creato correttamente',
                    'email' => $data['email'],
                    'password' => $data['password']
                ], 200);
            }

    }

}
