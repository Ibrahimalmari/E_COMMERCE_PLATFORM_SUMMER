<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_admin()
    {
        $section = Section::all();
        return response()->json([
           'status' => 200, 
           'section' =>$section,
           'message'=>' Successe',
       ]);
    }



   

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $id)
    {
        try {
            // Check if $id is present and valid
            if (!$id) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid user ID.',
                ]);
            }
    
            $validatedData = Validator::make($request->all(), [
                'name' => 'required|regex:/^[\p{Arabic}\s]+$/u|unique:sections|unique:products|unique:categories|unique:branches|unique:admins|unique:seller_men|unique:stores|unique:delivery_men', // تحقق من عدم تكرار الاسم في جدول sections
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }
    
            $section = Section::create([
                'name' => $request->name,
                'created_by' => $id,
            ]);
    
            return response()->json([
                'status' => 200,
                'section' => $section,
                'message' => 'Section added successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An unexpected error occurred. Please try again later.',
                'id' => $id
             ]);
        }
    }
    
    
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        

    $section = Section::find($id);

    
    if($section){
        return response()->json([
            'status' => 200, 
            'section' =>$section
        ]);
    }

    else{
        return response()->json([
            'status' => 404, 
            'message' =>'No section Id Found'
        ]);
    }
     
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'regex:/^[\p{Arabic}\s]+$/u',
                    Rule::unique('sections')->ignore($id),
                ],
            ]);
    
            if ($validatedData->fails()) {
                return response()->json([
                    'status' => 401,
                    'message' => $validatedData->errors()->first(),
                ]);
            }
    
            $section = Section::find($id);
    
            if (!$section) {
                return response()->json(['message' => 'Record not found'], 404);
            }
    
            // Check if there are any changes
            $oldName = $section->name;
            $newName = $request->name;
    
            if ($oldName === $newName) {
                return response()->json(['message' => 'No modifications were made'], 200);
            }
    
            // Update the record with the new value
            $section->update([
                'name' => $newName
            ]);
    
            return response()->json([
                'message' => 'Record updated successfully.',
                'section' => $section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An unexpected error occurred. Please try again later.',
            ]);
        }
    }
    


    
    
    


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $section = Section::find($id);
    
        if (!$section) {
            return response()->json([
                'status' => 404,
                'message' => 'Section not found',
            ], 404);
        }
    
        $section->delete();
    
        return response()->json([
            'status' => 200,
            'message' => 'Section deleted successfully',
        ]);
    }
    
}
