<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Topup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function send(Request $request){
        $user = User::findOrFail(auth()->guard('api')->id());
        if($user){
            $req = new Message();
            $req->user_id = $user->id;
            $req->message_id = Str::random(10);
            $req->message = $request->message;
            $req->sys = '0';
            $req->save();
            $data = '';
            switch(strtolower(trim($request->message))){
                case "balance":
                    $data = "Your current balance is ₦".$user->wallet->amount;  
                break;
                case "wallet_address":
                    $data = "Your account wallet ID: <b>".$user->wallet->address."</b>";  
                break;
                case "hello":
                    $data = "Hi,how are you doing?";
                break;
                case "hi":
                    $data = "Hello, how are you doing?";
                break;
                case "hey":
                    $data = "Hello, how are you doing?";
                break;
                case "fine":
                    $data = "Good to hear that";
                break;
                case "ok":
                    $data = "great";
                break;
                case "help":
                    $data = "Please type <b>commands</b> for list of commands";
                break;
                case "thanks":
                    $data = "You are welcome";
                break;
                case "thank you":
                    $data = "You are welcome";
                break;
                case "commands":
                    $data = "
                    <li><b>commands</b> &nbsp;&nbsp; <i> to see list of commands</li>
                    <li><b>balance</b> &nbsp;&nbsp; <i> to see account balance</li>
                    <li><b>history</b> &nbsp;&nbsp; <i> to see topup history</li>
                    <li><b>topup</b> &nbsp;&nbsp; <i> to make new topup request</li>
                    <li><b>book_ticket</b> &nbsp;&nbsp; <i> to make new ticket booking request</li>
                    <li><b>wallet_address</b> &nbsp;&nbsp; <i> to see account wallet address</li>
                    ";
                break;
                case "topup":
                    $data = '<a href="/account/wallet" class="bg-white rounded-md py-2 px-6 text-blue-900 font-bold">Click To Topup </a>';
                break;
                case "book_ticket":
                    $data = '<a href="/account/wallet/ticket" class="bg-white rounded-md py-2 px-6 text-blue-900 font-bold">Click To Book Ticket </a>';
                break;


                case "history":
                    $history = $user->topup()->orderBy('id', 'desc');
                    $data = '<div class="font-bold">
                    Date - Amount  - Status  - Remarks
                    </div><hr>';
                    foreach($history as $h){
                        $data += `<div>
                        {date('Y-m-d H:iA', strtotime($h->created_at))}  - ₦{$h->amount}  - {$h->status}  - {$h->remarks} 
                        </div><hr>`;
                    }
                break;

                default:
                    $data = "Command not recognized";
                break;
            }
            $req = new Message();
            $req->user_id = $user->id;
            $req->message_id = Str::random(10);
            $req->message = $data;
            $req->sys = '1';
            $req->save(); 
            return response()->json([
                'status' => "success",
                'proceed' => 1,
                'message' => 'Request sent successfully',
                'data' => []
            ], 200);
        }
        abort(404);
    }

    public function requests(){
        $user = User::findOrFail(auth()->guard('api')->id());
        if($user){
            $req = Message::where('user_id', $user->id)->orderBy('id', 'desc')->take(8)->get();
            return response()->json([
                'status' => "success",
                'proceed' => 1,
                'message' => 'Request sent successfully',
                'data' => ($req)
            ], 200);
        }
        abort(404);
    }
}
