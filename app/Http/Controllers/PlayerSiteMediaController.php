<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class PlayerSiteMediaController extends Controller
{
    public function getHomePageBanner($filename){
        $headers = [
            'Content-Type'        => 'Content-Type: application/png',
            'Content-Disposition' => 'attachment; filename="'. $filename .'"',
        ];

        return Response::make(Storage::disk('s3')->get('public/media/player/banners/' .$filename),200, $headers);
    }

    public function setHomePageBanner(Request $request){
        $path = Storage::putFileAs('public/media/player/banners/',$request->file('file'), $request->name);
        return $path;
    }
}
