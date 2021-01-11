<?php

namespace App\Http\Controllers;

use App\Resize;
use Illuminate\Http\Request;
use App\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{

    public function create()
    {
        return view('images.create');
    }

    public function store(Request $request)
    {
        $relative_path = 'images/sub_path';
        $path = $request->file('file')->store($relative_path, 's3');

        Storage::disk('s3')->setVisibility($path, 'private');

        $image = Image::create([
            'filename' => basename($path),
            'url' => Storage::disk('s3')->url($path)
        ]);

        return $image;
    }

    public function storewithResize(Request $request)
    {
        $relative_path = '';
        $insertImages = [];
        if($request->hasfile('file'))
        {
            $file = $request->file('file');
            $filename= $file->getFilename();
            $name = time().'-'.$filename.'.webp';
            $allowed = array('bmp','gif','jpg','jpeg','png','webp');
            $ext = $file->getClientOriginalExtension();
            $size= $file->getSize();
            if($size > 5242880){
                $response['msg']="File size exceeds the maximum amount (5MB) !";
                return response()->json($response);
            }
            if (!in_array($ext, $allowed)) {
                return response()->json("File type not supported");
            }

                $relative_path = "/assets/";
                $targetPath=$relative_main_path = $relative_path.$name;

                //save
                $s3path = $request->file('file')->store($relative_path, 's3');
                Storage::disk('s3')->setVisibility($s3path, 'private');
                $thumb_targetPath=$relative_path."thumbs/". $name;

                Resize::resizeing_image($file, "assets/slider_thumbs/" . $name, '760');
                Resize::resizeing_image_mark_slider($file,"assets/slider_thumbs/". $name);
                Resize::resizeing_image_mark($file,$relative_path. $name);
                Resize::resizeing_image($file, "assets/thumbs/" . $name, 300);
                $response['path']=$targetPath;
                $response['thumb_path']=$thumb_targetPath ;
                $response['file_name']=$name;
                $response['status'] = 1;
                $response['result'] = 1;
                $response['response_code'] =  200 ;
                $response['msg']="sucessfully uploaded !";
        }else{
            $response['msg']="No files found !";
            return response()->json($response);
        }



        $image = Image::create([
            'filename' => basename($s3path),
            'url' => Storage::disk('s3')->url($s3path)
        ]);

        return $response;
    }

    public function show(Image $image)
    {
        return $image->url;
    }
}
