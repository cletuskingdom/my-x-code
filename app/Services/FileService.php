<?php 
namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Image;
// use Intervention\Image\Facades\Image;
// use Intervention\Image\Image;

class FileService
{
    public static function upload($request, $fileName, $disk, $directory, $oldFile=null){

        if ($request->hasFile($fileName)) {
            if(!is_null($oldFile)){
                if (Storage::disk($disk)->exists($directory.'/'.$oldFile)) {
                    Storage::disk($disk)->delete($directory.'/'.$oldFile);
                }  
            }
            return self::finishUpload($request->$fileName, $disk, $directory);
            
        }
    }

    private static function finishUpload($uploadFile, $disk, $directory){
        $uploadFileName   = $uploadFile->getClientOriginalName();
        $uploadFileExt    = $uploadFile->getClientOriginalExtension();
        $uploadFileName   = pathinfo(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $uploadFileName)), PATHINFO_FILENAME);
        $uploadFileToDb   = $uploadFileName . '_' . time() . '.' . $uploadFileExt;
        

        //$uploadFile->getSize()/1000000;
        

        // if(in_array($uploadFileExt, ['png', 'jpg', 'jpeg', 'gif', 'svg']) &&  $file_size_mb > 1){
            
        //     $uploadFile = Image::make($uploadFile->path());
        //     $file_height = $uploadFile->height();
        //     $file_width = $uploadFile->width();
            
        //         $new_file_height = ( 0.9 * $file_height) / $file_size_mb;
        //         $new_file_width = ( 0.9 * $file_width) / $file_size_mb;
            
        //     $uploadFile->resize($new_file_width, $new_file_height, function ($constraint) {
        //         $constraint->aspectRatio();
        //     })->save(config('filesystems.disks.'.$disk.'.root').'/'.$directory.'/'.$uploadFileToDb);
        //     return $directory.'/'.$uploadFileToDb;
        // }

        if(in_array($uploadFileExt, ['png', 'jpg', 'jpeg', 'gif', 'svg'])){
            $uploadFile->storeAs($directory, $uploadFileToDb, $disk);
        }
        return $uploadFileToDb;
    }
    
}