<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;
use Validator;

class NotificationController extends Controller
{
    //Delete the notification
    public function delete(Request $request)
        {
           $id = $request->id;
           $validator = Validator::make($request->all(), [
               'id' => 'required|numeric',
           ]);
           if ($validator->fails()) {
                return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
           }          
           $notification = Notification::where('user_id', $request->get('myUser'))->find($id);
           if ($notification !== null) {
              $notification->delete();
           } else  {
                return response()->json(['response'=>'error', 'message'=>__('notification.missing')], 400);     
           }
           $notifications = Notification::where('user_id', $request->get('myUser'))->orderBy('created_at','DESC')->get();
           return response()->json($notifications, 200);
        }
   
    //Delete the notification
    public function markAsRead(Request $request)
        {
           $id = $request->id;
           $validator = Validator::make($request->all(), [
               'id' => 'required|numeric',
           ]);
           if ($validator->fails()) {
                return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
           }   
   
          $notification = Notification::where('user_id', $request->get('myUser'))->find($id);
          if ($notification == null) {
                return response()->json(['response'=>'error', 'message'=>__('notification.missing')], 400);               
          }
          $notification->isRead = true;
          $notification->save();
          $notifications = Notification::where('user_id', $request->get('myUser'))->orderBy('created_at','DESC')->get();
          return response()->json($notifications, 200);
        }    
   
    //Delete the notification
    public function getAll(Request $request)
       {
          //We need to get current User
          $notifications = Notification::where('user_id', $request->get('myUser'))->orderBy('created_at','DESC')->get();
          return response()->json($notifications, 200);
        }      
}
