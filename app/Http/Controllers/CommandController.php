<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommandController extends Controller
{
    public function main($name){
        $user = User::findOrFail($this->guard()->id());
        if($user){
            $data = '';
            switch(strtolower(trim($name))){
                case "balance":
                    $data = $user->wallet->amount;
                    $req = new Message();
                    $req->user_id = $user->id;
                    $req->message_id = Str::random(10);
                    $req->message = $data;
                    $req->sys = '1';
                    $req->save();
                break;

                case "topup":
                    $link = "";
                break;

                case "history":
                    $history = $user->topup()->orderBy('id', 'desc');
                    $data = '';
                    foreach($history as $h){
                        $data += `<div>
                        {$h->created_at}  - {$h->amount}  - {$h->status}  - {$h->remarks} 
                        </div><hr>`;
                    }
                    $req = new Message();
                    $req->user_id = $user->id;
                    $req->message_id = Str::random(10);
                    $req->message = $data;
                    $req->sys = '1';
                    $req->save(); 
                break;

                default:
                    $data = "Command not recognized";
                    $req = new Message();
                    $req->user_id = $user->id;
                    $req->message_id = Str::random(10);
                    $req->message = $data;
                    $req->sys = '1';
                    $req->save(); 
                break;

                return response()->json([
                    'status' => 'success',
                    'proceed' => '1',
                    'message' => 'Fetched successfully',
                    'data' => ""
                ]);

            }
        }
        abort(404);
    }
}
