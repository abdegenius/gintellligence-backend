<?php
namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->only('phone');
        $validator = Validator::make($data, [
            'phone' => 'required|unique:users',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'success',
                'proceed' => 0,
                'message' => 'Please select another phone number, phone number provided already in use by another user',
                'data' => ''
            ], 201);
        }

        //Request is valid, create new user
        $user = new User();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->password = bcrypt($request->password);
        $user->save();
        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->amount = 0;
        $wallet->address = ucwords(Str::random(6));
        $wallet->save();
        //User created, return success response
        return response()->json([
            'status' => "success",
            'proceed' => 1,
            'message' => 'User created successfully',
            'data' => []
        ], 200);
    }


    public function login(Request $request){
        $credentials = $request->only('phone', 'password');
        if ($token = $this->guard()->attempt($credentials)) {
            return response()->json([
                'status' => 'success',
                'proceed' => 1,
                'message' => 'Login successful',
                'token' => $this->respondWithToken($token),
                'data' => $this->guard()->user(),
            ], 200);
        }
        return response()->json([
            'status' => 'success',
            'proceed' => 0,
            'message' => 'Login credentials are invalid.',
            'data' => '',
        ], 201);
    }

    public function profile(){
        return response()->json([
            'status' => 'success',
            'proceed' => 1,
            'message' => 'Login successful',
            'data' => $this->guard()->user(),
        ]);
    }

    public function logout(){
        $this->guard()->logout();
        return response()->json([
            'status' => 'success',
            'proceed' => 1,
            'message' => 'User has been logged out',
            'data' => '',
        ], 200);
    }



    public function edit(Request $request){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $user->name = $request->name;
            $user->department = $request->department;
            $user->level = $request->level;
            $user->update();
            return response()->json([
                'status' => 'success',
                'proceed' => 1,
                'message' => 'User profile updated successfully.',
                'data' => $user,
            ], 200);
        }
        abort(404);
    }

    public function dashboard(){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $wallet_balance = $user->wallet->amount;
            $income = $user->transaction()->where([
                'user_id' => $user->id,
                'status' => 'successful',
                'type' => 'credit'
            ])
            ->where('created_at', 'like', '%'.date('Y-m-d').'%')
            ->sum('amount');
            $spending = $user->transaction()->where([
                'user_id' => $user->id,
                'status' => 'successful',
                'type' => 'debit'
            ])
            ->where('created_at', 'like', '%'.date('Y-m-d').'%')
            ->sum('amount');
            return response()->json([
                'status' => 'success',
                'proceed' => 1,
                'message' => 'Fetched successfully.',
                'data' => [
                    'wallet_balance' => $wallet_balance,
                    'income' => $income,
                    'spending' => $spending
                ],
            ], 200);
        }
        abort(404);
    }

    public function balance(){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $wallet_balance = $user->wallet->amount;
            return response()->json([
                'status' => 'success',
                'proceed' => 1,
                'message' => 'Fetched successfully.',
                'data' => [
                    'wallet_balance' => $wallet_balance
                ],
            ], 200);
        }
        abort(404);
    }

    public function contact(Request $request){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $user->contact = $request->address;
            $user->update();
            return response()->json([
                'status' => 'success',
                'proceed' => 1,
                'message' => 'User contact address updated successfully.',
                'data' => $user,
            ], 200);
        }
        abort(404);
    }

    public function security(Request $request){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            if(Hash::check($request->old_password, $user->password)){
                return response()->json([
                    'status' => 'success',
                    'proceed' => 0,
                    'message' => 'Old password is incorrect.',
                    'data' => '',
                ], 200);
            }
            elseif($request->password !== $request->password_again){
                return response()->json([
                    'status' => 'success',
                    'proceed' => 0,
                    'message' => 'Password confirmation failed.',
                    'data' => '',
                ], 200);
            }
            else{
                $user->password = bcrypt($request->password);
                $user->update();
                return response()->json([
                    'status' => 'success',
                    'proceed' => 1,
                    'message' => 'User password updated successfully.',
                    'data' => $user,
                ], 200);
            }
        }
        abort(404);
    }

    public function refresh(){
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token){
        return $token;
    }

    public function guard(){
        return Auth::guard('api');
    }
}
?>