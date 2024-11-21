<?php

namespace App\Traits;
use App\Models\File;
use App\Models\File_before_accept;
use App\Models\FileEdit;


trait Imageable
{


    public static function sssave($getFiles, $id): array
    {
        $uploadedFileIds = [];

        foreach ($getFiles as $getFile) {
            $filename = $getFile->getClientOriginalName();
            $extension = $getFile->getClientOriginalExtension();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
            $path = 'fileEdit';

            $getFile->move($path, $name);

            $save = File::create([
                'name' => $name,
                'path' => $path . '/' . $name,
                'extension' => $extension,
                'state' => 0
            ]);


            $uploadedFileIds[] = $save->id;
        }

        return $uploadedFileIds;
    }

    public static function ssssave($getFiles): array
    {
        $uploadedFiles = [];

        foreach ($getFiles as $getFile) {
            $filename = $getFile->getClientOriginalName();
            $extension = $getFile->getClientOriginalExtension();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
            $path = 'fileEdit';

            $getFile->move($path, $name);

            $save = FileEdit::create([
                'name' => $name,
                'path' => $path . '/' . $name,
                'extension' => $extension,
            ]);

            $uploadedFiles[] = $save;
        }

        return $uploadedFiles;
    }

    public static function ssave($getFiles, $id): void
    {
        foreach ($getFiles as $getFile) {
            $filename = $getFile->getClientOriginalName();
            $extension = $getFile->getClientOriginalExtension();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.' . $extension;
            $path = 'fileEdit';

            $getFile->move($path, $name);

            $save = File_before_accept::create([
                'name' => $name,
                'path' => $path . '/' . $name,
                'extension' => $extension,
                'state'=>0,
                'request_id'=>$id
            ]);
        }
    }


}



