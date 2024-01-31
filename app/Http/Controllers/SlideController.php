<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class SlideController extends Controller
{
    public function upload(Request $request)
    {
        try {
             $validate=Validator::make($request->all(),['img'=>'required']);

               if ($validate->fails()) {
                # code...
                return Response::json(['messages'=>$validate->getMessageBag(),'status'=>false]);
               } else {
                # code...
                if ($request->hasFile('img')) {

                    $image = $request->file('img');
                    $originalName = $image->getClientOriginalName();
                    $imageName = time() . '_' . $originalName;
                    $image->move(public_path('image'), $imageName);


                    Slide::create(['img'=>$imageName,'url'=>$request->url]);

                }
                return  Response::json([ 'message'=>"Image uploaded successfully",'status'=>true]);
               }

        } catch (\Throwable $th) {
            //throw $th;
            return  Response::json([ 'message'=> $th->getMessage(),'status'=>false]);

        }
    }
    public function uploadupdate(Request $request,$id)
    {
        try {
             $validate=Validator::make($request->all(),['img'=>'required']);

               if ($validate->fails()) {
                # code...
                return Response::json(['messages'=>$validate->getMessageBag(),'status'=>false]);
               } else {
                # code...
                if ($request->hasFile('img')) {

                    $image = $request->file('img');
                    $originalName = $image->getClientOriginalName();
                    $imageName = time() . '_' . $originalName;
                    $image->move(public_path('image'), $imageName);
                    $slide=Slide::findOrFail($id);

                    $slide->update(['img'=>$imageName,'url'=>$request->url]);

                }
                return  Response::json([ 'message'=>"Image uploaded successfully",'status'=>true]);
               }

        } catch (\Throwable $th) {
            //throw $th;
            return  Response::json([ 'message'=> $th->getMessage(),'status'=>false]);

        }
    }

    public function  getAllSlides(){

        $slider=Slide::latest()->get();

        return Response::json(['status'=>true,'slider'=>$slider]);
    }
}
