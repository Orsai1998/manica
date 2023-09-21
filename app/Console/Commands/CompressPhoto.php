<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CompressPhoto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compress:photo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apartments = Apartment::all();

        foreach ($apartments as $apartment){
            $directory = 'storage/app/public/apartments/'.$apartment->GUID;

            if (File::exists($directory) && File::isDirectory($directory)) {
                $files = File::files($directory);

                $imageFiles = array_filter($files, function ($file) {
                    return strpos(File::mimeType($file), 'image') === 0;
                });
                $images = [];
                foreach ($imageFiles as $key => $image) {
                   $new_folder = $directory.'/compressed/';
                    if(!File::exists($new_folder)){
                        File::makeDirectory($new_folder);
                    }
                    $this->compress($image, $new_folder.
                        $image->getFilenameWithoutExtension().'.'.$image->getExtension(),30);
                        File::delete($image);
                    echo "Compressed ".$apartment->GUID. "\n";
                }
            }
            else {
                echo "not found ". $apartment->GUID. " \n";
            }
        }
    }

    function compress($source, $destination, $quality) {

        try {
            $info = getimagesize($source);

            if ($info['mime'] == 'image/jpeg')
                $image = imagecreatefromjpeg($source);

            elseif ($info['mime'] == 'image/gif')
                $image = imagecreatefromgif($source);

            elseif ($info['mime'] == 'image/png')
                $image = imagecreatefrompng($source);

            imagejpeg($image, $destination, $quality);

            return $destination;
        }catch (\Exception $exception){
            return "";
        }
    }
}
