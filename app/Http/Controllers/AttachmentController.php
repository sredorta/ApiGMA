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
            'default' => "required_without:file|string|min:3",                              //Default data if not providing the file
            'file' => 'required_without:default|mimes:jpeg,bmp,png,gif,svg,pdf|max:2048',    //File that we are uploading, max 2M
            'alt_text' => 'nullable|string|min:2|max:100',
            'title' => 'required|string|min:5|max:100'
        ]);        
        $id = $request->attachable_id;
        $type = $request->attachable_type;
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        //Validate that id and type are attachables 
        if (!(class_exists($type) && method_exists($type, 'attachments'))) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_type', ['title' => $request->title])], 400); //422 ???
        }
        $subject = call_user_func($type . "::find", $id);
        if (!$subject) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.wrong_id', ['type' => $type, 'id'=>$id])], 400);
        }

        //If is user that isuploading make sure that he cannot upload two documents with exaclty same title
        if ($type === User::class) {
            if  ($subject->attachments()->where('title', $request->title)->get()->count() > 0) 
                return response()->json(['response'=>'error', 'message'=>__('attachment.already', ['type' => $type, 'id'=>$id])], 400);
        }

        //THIS IS THE PART TO CREATE A NEW ATTACHABLE WITH THUMBNAILS FOR IMAGES/DEFAULTS...
        $attachment = new Attachment($request->only('attachable_type','attachable_id'));
        $response = $attachment->getTargetFile($request->file('file'), $request->default);
        if ($response !== null) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.default', ['default' => $request->default])], 400);
        }
        $attachment->alt_text = $request->alt_text;
        $attachment->title = $request->title;
        $attachment->save(); //save and generate thumbs
        //END OF ATTACHABLE CREATION

        return response()->json([], 204);

    }

    //Delete document
    public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);        
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);
        }      
        $attachment = Attachment::find($request->id);
        if (!$attachment) {
            response()->json(['response'=>'error', 'message'=>__('attachment.wrong_id', ['id' => $request->id])], 400);
        }
        $attachment->remove();

        return response()->json([], 204);

    }
 
}