<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileController extends Controller
{

    public function storeFile(Request $request) {
        try {
            $data = [];
            if($request->hasFile('fileholder_files')) {

                $validator = Validator::make($request->all(),[
                    'fileholder_files' => 'required|mimes:'.$request->mimes,
                ]);
                if($validator->fails()) {
                    $data['error']  = $validator->errors()->all();
                    $data['status'] = false;
                    return response()->json($data,400);
                }

                $validated = $validator->safe()->all();

                $file_holder_files = $validated['fileholder_files'];
                $file_ext = $file_holder_files->getClientOriginalExtension();
                $file_store_name = Str::uuid() . "." . $file_ext;
                $data['path']   = url('fileholder/img/');
                $data['file_name']  =  $file_store_name;
                $data['file_link']  = $data['path'] . "/" . $data['file_name'];
                $data['file_type']  = $file_holder_files->getClientMimeType();
                $data['file_old_name']  = $file_holder_files->getClientOriginalName();

                $data['status'] = true;
                try{
                    File::move($file_holder_files,public_path('/fileholder/img/'.$file_store_name));
                    chmod(public_path('/fileholder/img/'.$file_store_name), 0644);
                }catch(Exception $e) {
                    log_error("FileController", "storeFile", $e->getMessage());
                    $data['status'] = false;
                }
            }else {
                $data['status'] = false;
                $data['error'] = __("Something went wrong! Please try again.");
            }
            return response()->json($data,200);
        } catch (\Throwable $th) {
            log_error("FileController", "storeFile", $th->getMessage());
            return response()->json([
                'status' => false,
                'error' => $th->getMessage()
            ], 500);
            //throw $th;
        }
    }

    public function removeFile(Request $request) {
        try {
            $validator = Validator::make($request->all(),[
                'file_info' => 'required|json',
            ]);

            if($validator->fails()) {
                $data['error']  = $validator->errors()->all();
                $data['status'] = false;
                return response()->json($data,400);
            }

            $validated = $validator->safe()->all();

            $file_path = '/fileholder/img';

            $file_info = json_decode($validated['file_info']);
            $data['status'] = true;
            try {
                FIle::delete(public_path($file_path.'/'.$file_info->file_name));
                $data['message'] = __("File Deleted Successfully!");
            }catch(Exception $e) {
                $data['status'] = false;
                $data['error'] = $e;
                $data['message'] = __("Something went wrong! Please try again.");
            }

            $data['file_info'] = $file_info;

            return response()->json($data,200);
        }catch (\Throwable $th) {
            log_error("FileController", "removeFile", $th->getMessage());
            return response()->json([
                'status' => false,
                'error' => $th->getMessage()
            ], 500);
        }

    }
}
