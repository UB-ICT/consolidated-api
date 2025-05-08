<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*

This is one controller that handles both upload and download functionality.

The uploadMeetingMinutes() function, allows you to upload files. This is 
stored under storage > uploads > meetings 

The uploadEventPhoto() function, allows you to upload files. This is 
stored under storage > uploads > photos

The downloadFile() function,  allows you to download files but requires two 
parameters, pass the file type and the file name. The file type is 
photos or meeting.

Author: SW

*/

class FileUploadsController extends Controller
{
    //New upload Meeting Minutes function
    public function uploadMeetingMinutes(Request $request)
    {
        try {
            
            # return response($request, 200);
            $result = Array();
            
            if ($files = $request->file('file')) {
                foreach ($files as $file) {
                    $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('uploads/meetings', $fileName);
                    array_push($result,['generated_name' => $fileName,'original_name' => $file->getClientOriginalName(),]);
                }
            }
    
            // Constructing the response with multiple file information
            $response = [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ];              

        }catch(\Exception $e){
        // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    
        return response($response, 200);
    }
    
    // public function uploadMeetingMinutes(Request $request){
    //     try{

    //         $file = $request->file('file');
    //         $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
    //         $file->storeAs('uploads/meetings', $fileName);
    
    //         //This is the save directly to the db 
    //         //Another way to do it is to save to the db and return the id of the saved item
    //         // File::create([ 
    //         //     'original_name' => $file->getClientOriginalName(),
    //         //     'generated_name' => $fileName,
    //         // ]);

    //         //Implementing it this way returns information that might be usefull not sure what the full usecase for this would be
    //         //Saving to the report data
    //         $response = [
    //             'success' => true,
    //             'message' => 'File uploaded successfully',
    //             'data' => [
    //                 'original_name' => $file->getClientOriginalName(),
    //                 'generated_name' => $fileName,
                    
    //             ]
    //         ];              

    //     }catch(\Exception $e){
    //     // Exception occurred
    //         $response = [
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'data' => null
    //         ];
    //     }

    //     return response($response, 200);

    // }

    public function uploadEventPhoto(Request $request){
        try {
            # return response($request, 200);
            $result = Array();
            if($files=$request->file('file')){
              foreach($files as $file) {
                $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads/photos', $fileName);
                
                array_push($result, ["generated_name" => $fileName, "original_name" => $file->getClientOriginalName()]);
              }
            } 
            #$file = $request->file('file');

            //Implementing it this way returns information that might be usefull not sure what the full usecase for this would be
            //Saving to the report data
            $response = [
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $result
            ];              

        }catch(\Exception $e){
        // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }


    public function downloadFile(Request $request, string $fileType, string $fileName){
        try{
            $filePath = storage_path('app/uploads/'. $fileType . '/' . $fileName);

            if (file_exists($filePath)) {
                return response()->download($filePath);

                // $response = [
                //     'success' => true,
                //     'message' => 'File downloaded successfully',
                //     'data' => [
                //         'original_name' => $file->getClientOriginalName(),
                //         'generated_name' => $fileName,
                //         'file_path' => "app/uploads/meetings/" . $fileName,
                //     ]
                // ];       
            } else {
                // abort(404, 'File not found');
                $response = [
                    'success' => false,
                    'message' => 'File not found',
                    'data' => null
                ];
            }
        }catch(\Exception $e){
        // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }

        return response($response, 200);
    }
}

