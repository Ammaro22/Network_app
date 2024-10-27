<?php

namespace App\Traits;
use App\Models\File;
use App\Models\File_before_accept;

//use http\Env\Request;

trait Imageable
{

    public static function sssave($getFiles, $id): array
    {
        $uploadedFileIds = []; // مصفوفة لتخزين المعرفات

        foreach ($getFiles as $getFile) {
            $filename = $getFile->getClientOriginalName();
            $extension = $getFile->getClientOriginalExtension();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
            $path = 'file';

            $getFile->move($path, $name);

            // تخزين الملف في قاعدة البيانات
            $save = File::create([
                'name' => $name,
                'path' => $path . '/' . $name,
                'extension' => $extension,
                'state' => 0
            ]);

            // إضافة المعرف إلى المصفوفة
            $uploadedFileIds[] = $save->id;
        }

        return $uploadedFileIds; // إرجاع المعرفات
    }
    public static function ssave($getFiles, $id): void
    {
        foreach ($getFiles as $getFile) {
            $filename = $getFile->getClientOriginalName();
            $extension = $getFile->getClientOriginalExtension();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
            $path = 'file';

            $getFile->move($path, $name);

            $save = File_before_accept::create([
                'name' => $name,
                'path' => $path,
                'extension' => $extension,
                'state'=>0,
                'request_id'=>$id
            ]);
        }
    }

}



