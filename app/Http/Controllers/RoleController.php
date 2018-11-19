<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Role;
use App\User;
use App\Notification;

class RoleController extends Controller
{
    //Get all roles available
    public function getRoles(Request $request) {
        $roles = Role::all();
        return response()->json($roles->toArray(),200); 
    }

    //Adds a role from a user
    public function attachUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'role_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }          
        $role = Role::find($request->role_id);
        $user = User::find($request->user_id);
        if (!$user || !$role) {
            return response()->json(['response'=>'error', 'message'=>__('role.missing')], 400);             
        }
        //Remove precedent attachment if is unique
        if ($role->isUnique) {
            DB::table('role_user')->where('role_id', $role->id)->delete();
        }
        $notif = new Notification;
        $notif->text = __('role.assign', ['role'=> Role::find($request->role_id)->name]);
        $user->notifications()->save($notif);
        $user->roles()->attach($request->role_id);
        return response()->json(null,204); 
    }

    //Removes a role from a user
    public function detachUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'role_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);   
        }
        $role = Role::find($request->role_id);
        $user = User::find($request->user_id);
        if (!$user || !$role) {
            return response()->json(['response'=>'error', 'message'=>__('role.missing')], 400);     
        }      
        $user->roles()->detach($role->id);
        $notif = new Notification;
        $notif->text = __('role.unassign', ['role'=> Role::find($request->role_id)->name]);
        $user->notifications()->save($notif);
        return response()->json(null,204); 
    }
    
    //create a new Role
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50',
            'isUnique' => 'nullable|boolean',
            'description' => 'required|min:10|max:500'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);    
        }
        $isUnique = $request->isUnique;
        if ($request->isUnique == null) $request->isUnique = false;
        $role = Role::create([
            'name' => $request->name,
            'isUnique' => $isUnique,
            'description' => $request->description
        ]);     
        return response()->json($role,200); 
    }    

     //delete a Role
     public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }          
        //When a role is removed all attached roles in the pivot are removed !
        $role = Role::find($request->id);
        if ($role == null) {
            return response()->json(['response'=>'error', 'message'=>__('role.missing')], 400); 
        }
        Role::find($request->id)->delete();
        return response()->json(null,204); 
    }       
}
