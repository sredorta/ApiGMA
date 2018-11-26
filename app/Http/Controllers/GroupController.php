<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Group;
use App\User;
use App\Notification;

class GroupController extends Controller
{

    //Get all roles available
    public function getAll(Request $request) {
        $groups = Group::all();
        return response()->json($groups->toArray(),200); 
    }

    //Adds a group from a user
    public function attachUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'group_id' => 'required|numeric|exists:groups,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }          
        $group = Group::find($request->group_id);
        $user = User::find($request->user_id);

        $notif = new Notification;
        $notif->text = __('notification.group_assign', ['group'=> $group->name]);
        $user->notifications()->save($notif);

        $user->groups()->attach($request->group_id);
        return response()->json(null,204); 
    }

    //Removes a group from a user
    public function detachUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric|exists:users,id',
            'group_id' => 'required|numeric|exists:groups,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);   
        }
        $group = Group::find($request->group_id);
        $user = User::find($request->user_id);

        $user->groups()->detach($group->id);
        $notif = new Notification;
        $notif->text = __('notification.group_unassign', ['group'=> $group->name]);
        $user->notifications()->save($notif);
        return response()->json(null,204); 
    }
    
    //create a new Role
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:100|unique:groups,name',
            'description' => 'required|min:10|max:500'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);    
        }
        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description
        ]);     
        return response()->json($group,200); 
    }    

     //delete a Role
     public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:groups,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }          
        //When a role is removed all attached roles in the pivot are removed !
        $group = Group::find($request->id);

        Group::find($request->id)->delete();
        return response()->json(null,204); 
    }       
}
