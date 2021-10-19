<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Topup;
use App\Models\Transaction;
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
        $user->email = $request->email;
        $user->otp = substr(rand(),0,5);
        $user->save();
        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->amount = 0;
        $wallet->address = ucwords(Str::random(6));
        $wallet->save();
        $req = new Message();
        $req->user_id = $user->id;
        $req->message_id = Str::random(10);
        $req->message = 'Welcome to G-INTELLIGENCE, please type any of our commands to get start. for full list of command please send <b class="text-blue-500">commands</b> in the messaging interface below';
        $req->sys = '1';
        $req->save();
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
            $user->email = $request->email;
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

    public function virtual_account() {
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $virtual_account_count = $user->virtual_account()->count();
            if($virtual_account_count > 0){
                return response()->json([
                    'status' => 'success',
                    'proceed' => 0,
                    'message' => 'User have virtual account.',
                    'data' => $user->virtual_account,
                ], 200);
            }
            else{
                return response()->json([
                    'status' => 'success',
                    'proceed' => 0,
                    'message' => 'User does not have a virtual account.',
                    'data' => '',
                ], 200);
            }
        }
        abort(404);
    }

    public function initiate(Request $request){
        $user = User::findOrFail($this->guard()->id());
        $data = '';
        if($user){
$curl = curl_init();
$email = $user->email;
$amount = $request->amount*100;
$callback_url = 'http://127.0.0.1:8000/api/verify/paystack';  
curl_setopt_array($curl, array(
CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_CUSTOMREQUEST => "POST",
CURLOPT_POSTFIELDS => json_encode([
    'amount'=>$amount,
    'email'=>$email,
    'callback_url' => $callback_url
]),
CURLOPT_HTTPHEADER => [
    "authorization: Bearer sk_test_a1db54c8f3b8c0888c4dee963e2afec7f2133e08", 
    "content-type: application/json",
    "cache-control: no-cache"
],
));

$response = curl_exec($curl);
$err = curl_error($curl);

if($err){
$data = $err;
}

$tranx = json_decode($response, true);

if(!$tranx['status']){
    $data = $tranx['message'];
}
$topup = new Topup();
$topup->user_id = $user->id;
$topup->amount = $request->amount;
$topup->method = "card";
$topup->status = "pending";
$topup->reference = $tranx['data']['reference'];
$topup->save();
$transaction = new Transaction();
$transaction->user_id = $user->id;
$transaction->amount = $request->amount;
$transaction->method = "card";
$transaction->status = "pending";
$transaction->type = "credit";
$transaction->reference = $tranx['data']['reference'];
$transaction->save();
if($data != ''){
    return response()->json([
        'status' => 'success',
        'proceed' => 0,
        'message' => 'Cannot process payment',
        'data' => $data,
    ], 200);
}
else{
    return response()->json([
        'status' => 'success',
        'proceed' => 1,
        'message' => 'Cannot process payment',
        'data' => $tranx['data']['authorization_url'],
    ], 200);
}

}
abort(404);
    }

public function paystack(){          

$curl = curl_init();
$reference = isset($_GET['reference']) ? $_GET['reference'] : '';
if(!$reference){
    // $data = 'No reference supplied';
}
curl_setopt_array($curl, array(
CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
CURLOPT_RETURNTRANSFER => true,
CURLOPT_HTTPHEADER => [
    "accept: application/json",
    "authorization: Bearer sk_test_a1db54c8f3b8c0888c4dee963e2afec7f2133e08",
    "cache-control: no-cache"
],
));
$response = curl_exec($curl);
$err = curl_error($curl);
if($err){
    // $data = $err;
}
$tranx = json_decode($response);
if(!$tranx->status){
    // $data = $tranx->message;
}
if('success' == $tranx->data->status){
    $topup =  Topup::where('reference', $reference)->first();
    if($topup){
        $wallet = Wallet::where('user_id', $topup->user_id)->first();
        $transaction = Transaction::where('reference', $reference)->first();
        $topup->status = 'successful';
        $topup->update();
        $transaction->status = 'successful';
        $transaction->update();
        $wallet->amount += $topup->amount;
        $wallet->update();

        return "Account Topup successfully.";
    }
}
    }

}
?>