<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Support\Facades\Validator;
use App\Models\Store_Section;
use Illuminate\Http\Request;

class Store_Section_Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Store_Section = Store_Section::all();
        return response()->json([
           'status' => 200, 
           'Store_Section' =>$Store_Section,
           'message'=>' Successe',
       ]);
    }

    public function getSectionsForStore($type)
    {
        // جلب المتاجر بناءً على النوع المرسل
        $stores = Store::where('type', $type)->get();
        
        // التحقق من وجود المتاجر
        if ($stores->isEmpty()) {
            return response()->json([
                'status' => 404,
                'sections' => [],
                'message' => 'No stores found for the specified type.',
            ]);
        }
    
        // الحصول على الأقسام الخاصة بكل متجر
        $sections = [];
        foreach ($stores as $store) {
            $storeSections = Store_Section::where('store_id', $store->id)->get();
            $sections = array_merge($sections, $storeSections->toArray());
        }
    
        return response()->json([
            'status' => 200,
            'sections' => $sections,
            'message' => 'Success',
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
        $validatedData = Validator::make($request->all(), [
            'section_id' => 'required',
            'store_id' => 'required',
        ]);
    
        if ($validatedData->fails()) {
            return response()->json([
                'validation_error' => $validatedData->messages(),
            ]);
        }
    
        // Check if the combination of section_id and store_id already exists
        $existingRecord = Store_Section::where('section_id', $request->section_id)
                                        ->where('store_id', $request->store_id)
                                        ->exists();
    
        if ($existingRecord) {
            return response()->json([
                'status' => 401,
                'message' => 'This combination of section and store already exists.',
            ]);
        }
    
        try {
            // Create the store section if it doesn't already exist
            $store_section = Store_Section::create([
                'section_id' => $request->section_id,
                'store_id' => $request->store_id,
                'created_by'=> $id,
            ]);
    
            return response()->json([
                'status' => 200,
                'message' => 'Section to store added successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An unexpected error occurred. Please try again later.',
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
    $store_section = Store_Section::findOrFail($id);
    
    if($store_section){
    return response()->json([
        'status' => 200,
        'store_section' => $store_section,
    ]);
    }
    else{
        return response()->json([
            'status' => 404, 
            'message' =>'No data  Found'
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
            'section_id' => 'required',
            'store_id' => 'required',
        ]);

        if ($validatedData->fails()) {
            return response()->json([
                'validation_error' => $validatedData->messages(),
            ]);
        }

        // Check if the combination of section_id and store_id already exists
        $existingRecord = Store_Section::where('section_id', $request->section_id)
                                        ->where('store_id', $request->store_id)
                                        ->where('id', '!=', $id)
                                        ->exists();

        if ($existingRecord) {
            return response()->json([
                'status' => 401,
                'message' => 'The combination of section and store already exists.',
            ]);
        }

        // Check if there are any changes
        $store_section = Store_Section::findOrFail($id);
        if ($store_section->section_id == $request->section_id && $store_section->store_id == $request->store_id) {
            return response()->json([
                'status' => 201,
                'message' => 'No changes were made',
            ]);
        }

        // Update the store section
        $store_section->section_id = $request->section_id;
        $store_section->store_id = $request->store_id;
        $store_section->created_by= $id;
        $store_section->save();

        return response()->json([
            'status' => 200,
            'message' => 'Store section updated successfully',
            'store_section' => $store_section,
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
        $store_section = Store_Section::findOrFail($id);
        $store_section->delete();
    
        return response()->json([
            'status' => 200,
            'message' => 'Store section deleted successfully',
        ]);
    }
    
}
