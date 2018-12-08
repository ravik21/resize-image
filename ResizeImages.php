<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\Storage;
use Schema;

class ResizeImages extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:resize {--model=}';
    public $imageSize;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

       $model = new MODEL_NAME;

       //[width,height]
       $dimensions = [
         'thumbnail_path'     =>  [500,300],
         'mobile_path'        =>  [300,200],
         'facebook_feed_path' =>  [600,600]
       ];

       $i = 1;

       $images = $model::orderBy('created_at', 'asc')
                        ->get();

        foreach($images as $image){

          $j = 0;
          
          //For model Images
          if($image->image_path) {
           $imageUrl = 'https:'.$image->image_path;
           echo $i . '. Resizing ' .$imageUrl.PHP_EOL;
          }
          else{
            continue;
          }

          $imageSize = @getimagesize($imageUrl);

          if ($imageSize === FALSE) {
            \Log::info('Invalid IMAGE ID : '. $image->id);
            echo $i . '. Invalid Url ' .$imageUrl.PHP_EOL;
            throw new \Exception("Cannot access  to read contents.");
          } else {
            $this->imageSize = $imageSize;
          }

          $this->imageSize = $imageSize;

          ++$i;

          //New filename ===>   _50px_50px.jpg
          preg_match("/.*(\..*)/msi",$imageUrl,$match);
          $extension = $match[1];


          foreach($dimensions as $field => $dimension){

            if(!Schema::hasColumn($model->getTable(), 's3_'.$field))
            {
              echo 'Column not found ' .'s3_'.$field.PHP_EOL;
              continue;
            }


             if(!empty($image->{'s3_'.$field}))
              continue;

             $newImageUrl = str_replace($extension,"_".$dimension[0]."px_".$dimension[1]."px"  . $extension,$imageUrl);

             $fileNameWithPath = str_replace('https://YOUR_DOMAIN', '', $newImageUrl);
             $fileNameWithPath = str_replace(' ', '-', $fileNameWithPath);

             $compressedImageData = $this->resizeImage($image_url, $dimension[0], $dimension[1]);  //50px * 50px image.. change size if needed.

             if($compressedImageData)
             {
               Storage::disk('s3')->put($fileNameWithPath, $compressedImageData, 'public');

               $image->{'s3_'.$field} = str_replace('https:','',$newImageUrl);
               $image->save();

               
               $this->info('# Resized ::' .$imageUrl .' Medium : s3_'.$field . PHP_EOL . '  To::' . $newImageUrl );
               ++$j;
             }
             else
              $this->info('# Resizing failed ' .$imageUrl .' Medium : s3_'.$field);
          }
        }

    }

     function resizeImage($filename, $max_width, $max_height)
     {
        $imageSize = $this->imageSize;

        preg_match("/jpeg/",$imageSize['mime'],$match);

        if(!$match) return $filename;

        list($orig_width, $orig_height) = $imageSize;

        $width = $orig_width;
        $height = $orig_height;

        if ($height > $max_height) {
           $width = ($max_height / $height) * $width;
           $height = $max_height;
        }

        # wider
        if ($width > $max_width) {
           $height = ($max_width / $width) * $height;
           $width = $max_width;
        }

        $image_p = imagecreatetruecolor($width, $height);

        $image = imagecreatefromjpeg($filename);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0,
                                        $width, $height, $orig_width, $orig_height);

        //  header('Content-Type: image/jpeg');

        $path = public_path('tmp/'.str_random(5).'.jpg');
        imagejpeg($image_p,$path, 100);

        $return =  file_get_contents($path);

        unlink($path);

        return $return;

     }
}
