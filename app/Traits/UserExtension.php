<?php

namespace App\Traits;

use App\Models\UserDocument;
use Illuminate\Support\Str;

trait UserExtension
{

    public function saveUserDocument($isFront, $file, $user){
        $filename = "back_ID";
        if($isFront) {
            $filename = "front_ID";
        }
        $fullFileName = $filename.(string) Str::uuid().".".$file->getClientOriginalExtension();
        $filePath = $file->storeAs('documents', $fullFileName, 'public');

        if(!empty($filePath)){
            $userDoc = UserDocument::where('user_id', $user->id)->where('name', $filename)->first();
            if($userDoc){
                $userDoc->path = $filePath;
                $userDoc->isFrontSide = $isFront;
                $userDoc->save();
            }else{
                $this->createUserDocument($user->id, $filePath,$filename,$isFront);
            }

        }
    }

    protected function createUserDocument($user_id, $path, $name,$isFront){
        UserDocument::create([
            'user_id' => $user_id,
            'path' => $path,
            'name' => $name,
            'isFrontSide' => $isFront
        ]);
    }

}

