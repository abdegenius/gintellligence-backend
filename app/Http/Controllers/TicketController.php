<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function new(Request $request){
        $user = User::findOrFail(auth()->guard('api')->id());
        if($user){
            $wallet = $user->wallet;
            if($wallet && $wallet->amount >= $request->amount){
                $ticket = new Ticket();
                $ticket->user_id = $user->id;
                $ticket->ticket_number = rand(10000,1000000)."-".rand(10000,1000000);
                $ticket->amount = $request->amount;
                $ticket->status = 'active';
                $ticket->save();
                $wallet->amount -= $request->amount;
                $wallet->update();
                return response()->json([
                    'status' => "success",
                    'proceed' => 1,
                    'message' => "Ticket created successfully",
                    'data' => $ticket
                ], 200); 
            }
            
        }
        abort(404);
    }

    public function tickets(){
        $user = User::findOrFail(auth()->guard('api')->id());
        if($user){
            $tickets = Ticket::where('user_id', $user->id)->orderBy('id', 'desc')->get();
            return response()->json([
                'status' => "success",
                'proceed' => 1,
                'message' => 'Data fetched successfully',
                'data' => ($tickets)
            ], 200);
        }
        abort(404);
    }

    public function load(Request $request){
        $user = User::findOrFail(auth()->guard('api')->id());
        if($user){
            $ticket = Ticket::where('ticket_number', $request->ticket_number)->first();
            $wallet = $user->wallet;
            $ticket->used_by = $user->id;
            $ticket->status = 'used';
            $ticket->update();
            $wallet->amount += $request->amount;
            $wallet->update();
            return response()->json([
                'status' => "success",
                'proceed' => 1,
                'message' => "Ticket created successfully",
                'data' => $ticket
            ], 200); 
        }
        abort(404);
    }
}
