<?php
namespace App\Repositories;

use App\Models\Change;
use App\Models\File;
use App\Models\File_group;
use App\Models\Fileold;
use App\Models\Group;
use App\Traits\Imageable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Spatie\PdfToText\Pdf as SpatiePdf;

class FileRepository
{
    use Imageable;
    public function getFilesByGroupId($groupId, $perPage = 10)
    {
        return File::whereHas('file_group', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })->paginate($perPage);
    }

    public function findFileById($id)
    {
        return File::find($id);
    }
    public function findOldFileById($id)
    {
        return Fileold::find($id);
    }

    public function createFile(array $data)
    {
        return File::create($data);
    }

    public function deleteFile(File $file)
    {
        return $file->delete();
    }

    public function addFileToGroup($fileId, $groupId)
    {

        $group = Group::find($groupId);
        if (!$group) {
            throw new \Exception("Group not found.", 404);
        }

        $fileGroup = new File_group();
        $fileGroup->file_id = $fileId;
        $fileGroup->group_id = $groupId;
        $fileGroup->save();
    }
    public function processFileEdits($files)
    {

        $uploadedFiles = $this->ssssave($files);
        if (empty($uploadedFiles)) {
            throw new \Exception("No file edits found. Uploaded files array is empty.", 404);
        }

        return $uploadedFiles;
    }

    public function saveOldFileRecord($file, $groupId)
    {
        $filename = pathinfo($file->name, PATHINFO_FILENAME);
        $extension = pathinfo($file->name, PATHINFO_EXTENSION);

        do {
            $newName = $filename . '.' . $extension;
            $newPath = public_path('file/' . $newName);

        } while (file_exists($newPath));

        $oldFileRecord = new Fileold();
        $oldFileRecord->name = $newName;
        $oldFileRecord->path = 'file/' . $newName;
        $oldFileRecord->group_id = $groupId;
        $oldFileRecord->save();

        $destinationPath = public_path('file');
        $sourcePath = $file->path;

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $newFilePath = $destinationPath . '/' . $newName;
        rename($sourcePath, $newFilePath);

        return $oldFileRecord->id;
    }

    public function getSimilarFiles($fileName, $groupId)
    {

        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $cleanBaseName = preg_replace('/(_v\d+)?$/', '', $baseName);

        return Fileold::where('name', 'like', $cleanBaseName . '%')
        ->where('name', 'like', '%' . $extension)
        ->whereHas('group', function($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })
            ->get();
    }

    public function getChangesForFile($fileName, $groupId)
    {
        // استخراج الاسم واللاحقة
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $cleanBaseName = preg_replace('/(_v\d+)?$/', '', $baseName);


        $oldFiles = Fileold::where('name', 'like', $cleanBaseName . '%')
            ->where('name', 'like', '%' . $extension)
            ->whereHas('group', function($query) use ($groupId) {
                $query->where('group_id', $groupId);
            })
            ->get();

        // التحقق من وجود الملفات القديمة
        if ($oldFiles->isEmpty()) {
            return response()->json([
                'message' => 'No old files found for the specified file in this group.',
            ], 404);
        }

        // استرجاع التغييرات المرتبطة بالملفات القديمة
        $oldFileIds = $oldFiles->pluck('id'); // استخراج المعرفات
        $changes = Change::whereIn('file_old_id', $oldFileIds)->get();

        // التحقق من وجود تغييرات
        if ($changes->isEmpty()) {
            return response()->json([
                'message' => 'No changes found for the specified old files.',
            ], 404);
        }

        return response()->json($changes);
    }

    public function getTextFromWord($filePath)
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text;
    }

    public function getTextFromPdf($filePath)
    {
        return SpatiePdf::getText($filePath);
    }

    public function getTextFromExcel($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $text = '';

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $text .= 'Sheet: ' . $worksheet->getTitle() . "\n";
            $rows = $worksheet->toArray();

            foreach ($rows as $row) {
                $text .= implode("\t", $row) . "\n";
            }
        }

        return $text;
    }

    private function getTextFromFile($file)
    {
        $filePath = public_path($file->path);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'الملف غير موجود في المسار: ' . $filePath], 404);
        }

        switch ($file->extension) {
            case 'docx':
                return $this->getTextFromWord($filePath);
            case 'pdf':
                return $this->getTextFromPdf($filePath);
            case 'xls':
            case 'xlsx':
                return $this->getTextFromExcel($filePath);
            default:
                $content = file_get_contents($filePath);
                if (empty($content)) {
                    return response()->json(['error' => 'المحتوى فارغ.'], 404);
                }

                if (!mb_detect_encoding($content, 'UTF-8', true)) {
                    $convertedContent = iconv('Windows-1256', 'UTF-8//IGNORE', $content);
                    if ($convertedContent === false) {
                        return response()->json(['error' => 'فشل تحويل الترميز إلى UTF-8.'], 500);
                    }
                    return $convertedContent;
                }

                return $content;
        }
    }



    public function compareFiles(Request $request)
    {

        $originalFileId = $request->input('original_file_id');
        $modifiedFileId = $request->input('modified_file_id');
        $userName = $request->input('user_name');


        $originalFile = Fileold::find($originalFileId);
        $modifiedFile = File::find($modifiedFileId);

        if (!$originalFile) {
            return response()->json(['error' => 'Original file not found.'], 404);
        }

        if (!$modifiedFile) {
            return response()->json(['error' => 'Modified file not found.'], 404);
        }

        $originalContent = trim($this->getTextFromFile($originalFile));
        $modifiedContent = trim($this->getTextFromFile($modifiedFile));

        if ($originalContent === $modifiedContent) {
            try {

                $change = new Change();
                $change->change = 'لا توجد اختلافات بين الملفات.';
                $change->file_old_name = $originalFile->name;
                $change->file_new_name = $modifiedFile->name;
                $change->user_name = $userName;
                $change->file_old_id=$originalFile->id;
                $change->date_checkin = now();
                $change->save();

                return response()->json(['message' => 'لا توجد اختلافات بين الملفات ']);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Error saving changes to the database.',
                    'details' => $e->getMessage(),
                ], 500);
            }
        }

        $outputBuilder = new UnifiedDiffOutputBuilder();

        $differ = new Differ($outputBuilder);

        $diff = $differ->diff(explode("\n", $originalContent), explode("\n", $modifiedContent));

        $diffLines = explode("\n", $diff);

        $diffWithLineNumbers = [];
        $lineNumber = 1;

        foreach ($diffLines as $line) {
            if (preg_match('/^-/', $line)) {
                $diffWithLineNumbers[] = sprintf("%d: file_old: %s", $lineNumber++, $line);
            } elseif (preg_match('/^\+/', $line)) {
                $diffWithLineNumbers[] = sprintf("%d: file_new: %s", $lineNumber++, $line);
            } elseif (!preg_match('/^--- Original$|^\+\+\+ New$/', $line)) {
                $diffWithLineNumbers[] = sprintf("%d: %s", $lineNumber++, $line);
            }
        }

        $diffWithLineNumbers = array_slice($diffWithLineNumbers, 2);

        try {
            $change = new Change();
            $change->change = implode("\n", $diffWithLineNumbers);
            $change->file_old_name = $originalFile->name;
            $change->file_new_name = $modifiedFile->name;
            $change->user_name = $userName;
            $change->file_old_id=$originalFile->id;
            $change->date_checkin = now();
            $change->save();
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error saving changes to the database.',
                'details' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['differences' => $diffWithLineNumbers]);
    }



}
