<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Validator;
use App\User;
use App\Account;
use App\Attachment;
use App\kubiikslib\Helper;
use JWTAuth;

use Intervention\Image\ImageManager;

class AttachmentController extends Controller
{

    //Add document
    public function create(Request $request) {

        //Update firstName if is required
        $validator = Validator::make($request->all(), [
            'attachable_id' => 'required|numeric',
            'attachable_type' => 'required|string',
            'root' => "required|string|min:3",
            'default' => "required_without:file|string|min:3",                              //Default data if not providing the file
            'file' => 'required_without:default|mimes:jpeg,bmp,png,gif,svg,pdf|max:2048',    //File that we are uploading, max 2M
            'alt_text' => 'nullable|string|min:2|max:100',
            'title' => 'nullable|string|min:5|max:100'
        ]);        
        $id = $request->attachable_id;
        $type = $request->attachable_type;
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        //Validate that id and type are attachables 
        if (!(class_exists($type) && method_exists($type, 'attachments'))) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_type', ['type' => $type])], 400); //422 ???
        }
        $subject = call_user_func($type . "::find", $id);
        if (!$subject) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_id', ['type' => $type, 'id'=>$id])], 400);
        }
        $attachment = new Attachment($request->only('attachable_type','attachable_id'));
        $isDefault = false;
        if($request->file !== null) {
            $attachment->uploadFile($request->file('file')); //Automatically fills file_name, file_extension, file_size, url
        } else {
            $isDefault = true;
            $result = $attachment->getDefault($request->default); //Get default file and fills file_name...
            if ($result === null) {
                return response()->json(['response'=>'error', 'message'=>__('attachment.default', ['default' => $request->default])], 400);
            }
        }
        $attachment->alt_text = $request->alt_text;
        $attachment->title = $request->title;
        $attachment->save();
        //Now create the thumbs if this is an image and there is no default image
        if ($isDefault ==  false) {
            $attachment->createThumbs();
        }
        return response()->json([], 204);

    }

    //Delete document
    public function delete(Request $request) {
        //Update firstName if is required
        $validator = Validator::make($request->all(), [
            'attachable_id' => 'required|numeric',
            'attachable_type' => 'required|string',
        ]);        
        $id = $request->attachable_id;
        $type = $request->attachable_type;
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        //Validate that id and type are attachables 
        if (!(class_exists($type) && method_exists($type, 'attachments'))) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_type', ['type' => $type])], 400); //422 ???
        }
        $subject = call_user_func($type . "::find", $id);
        if (!$subject) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_id', ['type' => $type, 'id'=>$id])], 400);
        }
        //Delete attachment and associated thumbs if any
        foreach ($subject->attachments()->get() as $attachment) {
            $attachment->remove();
        }
        return response()->json([], 204);

    }
 
}