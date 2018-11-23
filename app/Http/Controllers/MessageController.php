<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\User;
use App\Message;
use App\Attachment;
use App\kubiikslib\Helper;
use JWTAuth;

class MessageController extends Controller
{
    public function send(Request $request) {
        //Validate data
        $validator = Validator::make($request->all(), [
            'subject'   => 'required|string|min:2|max:100',
            'text'      => 'required|string|min:5|max:1000',
            'to'        => 'required|array|min:1',
            'to.users'   => 'required_without:to.groups|array|distinct',
            'to.users.*' => 'numeric|distinct|exists:users,id',
//            'to.groups'   => 'required_without:to.users|array|distinct',
//            'to.groups.*' => 'numeric|distinct|exists:groups,id',

        ]);      
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }

        //TODO merge groups and users to get unique list !!!!!!!!!!!!!!
        $to_users = $request->get('to')['users'];
        //$to_groups = $request->get('to')['groups'];

        $to = $to_users; //This is for now while waiting for groups
        $from = $request->get("myUser");

        $message = Message::create([
            "subject" => $request->subject,
            "text" => $request->text
        ]);

        //Add the data in the pivot
        $userFrom = User::find($from);
        foreach ($to as $userTo) {
            $message->users()->save(User::find($userTo), ["from_user_id"=>$from, "from_user_first"=> $userFrom->firstName, "from_user_last"=>$userFrom->lastName, "isRead"=>false]);
        }

        return response()->json([], 204);
    }

    //Return our messages
    public function getAll(Request $request) {
        $user = User::find($request->get("myUser"));
        $result = [];
        foreach ($user->messages()->get() as $message) {
            $pivot = $message->pivot;
            $message->pivot = null;
            $message->from_id = $pivot->from_user_id;
            $message->from_first = $pivot->from_user_first;
            $message->from_last = $pivot->from_user_last;
            $message->isRead = $pivot->isRead;
            array_push($result, $message);
        }
        return response()->json($result,200);
    }

    //Return our messages
    public function markAsRead(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'   => 'required|exists:messages,id'
        ]);      
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        $user = User::find($request->get("myUser"));
        $user->messages()->where("message_id", $request->id)->updateExistingPivot($request->id, ['isRead' => true]);
 //       dd($user->messages()->get()->toArray());

  //      dd(Message::find($request->id)->users()->where("user_id", $user->id)->get()->toArray());
        //->update(Message::find($request->id), ["isRead"=>true]);
        //$user->messages()->where("message_id", $request->id)->update(['markAsRead' => true]);

        return response()->json([],204);
    }    

    //Delete message
    public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id'   => 'required|exists:messages,id'
        ]);      
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        $user = User::find($request->get("myUser"));
        $message = Message::find($request->id);
        $user->messages()->detach($request->id);
        if ($message->users()->get()->count() == 0) {
            $message->delete();
        }


        return response()->json([],204);
    }    

}
