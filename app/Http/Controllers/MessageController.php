<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\User;
use App\Group;
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
            'to.groups'   => 'required_without:to.users|array|distinct',
            'to.groups.*' => 'numeric|distinct|exists:groups,id',

        ]);      
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }
        if (array_key_exists('users', $request->get('to')))
            $to_users = $request->get('to')['users'];
        else 
            $to_users = [];
           
        if (array_key_exists('groups', $request->get('to')))
            $to_groups = $request->get('to')['groups'];
        else 
            $to_groups = [];

        foreach ($to_groups as $group) {
            foreach (Group::find($group)->users()->get() as $user) {
                if(!in_array($user->id, $to_users, true)){
                    array_push($to_users, $user->id);     
                }
            }
        }

        $to = $to_users; //This is for now while waiting for groups
        $from = $request->get("myUser");

        //Add the message to each to user
        $userFrom = User::find($from);
        foreach ($to as $userTo) {
            User::find($userTo)->messages()->create([
                "subject" => $request->subject,
                "text" => $request->text,
                "from_id" => $userFrom->id,
                "from_first" => $userFrom->firstName,
                "from_last" => $userFrom->lastName,
                "to_user_list" => implode(",", $to_users),
                "to_group_list" => implode(",", $to_groups)
            ]);
        }

        return response()->json([], 204);
    }

    //Return our messages
    public function getAll(Request $request) {
        $user = User::find($request->get("myUser"));
        return response()->json($user->messages()->get()->toArray(),200);
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
        $user->messages()->where("id", $request->id)->update(['isRead' => true]);

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
        $user->messages()->where('id', $request->id)->delete();

        return response()->json([],204);
    }    

}
