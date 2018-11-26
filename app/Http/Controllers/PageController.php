<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\User;
use App\Page;

class PageController extends Controller
{
    //Get all pages available
    public function getAll(Request $request) {
        $pages = Page::all();
        return response()->json($pages->toArray(),200); 
    }

    //Remove a page
    public function delete(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:pages,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }          
        //When a role is removed all attached roles in the pivot are removed !
        Page::find($request->id)->delete();
        return response()->json(null,204); 
    }    

    //create a new Page
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400);    
        }
        $page = Page::create([
            'content' => $request->content
        ]);     
        return response()->json($page,200); 
    }       


    public function getAttachments(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:pages,id'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }      
 
        return response()->json(Page::find($request->id)->attachments()->get()->toArray(),200); 
    }   
/*
    public function addAttachment(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:pages,id',
            'file' => 'required|mimes:jpeg,bmp,png,gif,svg|max:2048',
            'title' => 'required|string|min:2',
            'alt_text' => 'required|string|min:2'
        ]);
        if ($validator->fails()) {
            return response()->json(['response'=>'error', 'message'=>$validator->errors()->first()], 400); 
        }      

        $attachment = new Attachment;
        $attachment->attachable_id = $request->id;
        $attachment->attachable_type = Page::class;
        $response = $attachment->getTargetFile($request->file('file'), null);
        $response = $attachment->getTargetFile($avatar, $default);
        if ($response !== null) {
            return response()->json(['response'=>'error', 'message'=>__('attachment.default', ['default' => $default])], 400);
        }
        $attachment->alt_text = $request->alt_text;
        $attachment->title = $request->title;
        $attachment->save(); //save and generate thumbs

        return response()->json($attachment->toArray(),200); 
    }       */


}
