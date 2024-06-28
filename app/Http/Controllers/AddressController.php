<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        $request->validate([
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'area' => 'nullable|string',
            'street' => 'nullable|string',
            'nearBy' => 'nullable|string',
            'additionalDetails' => 'nullable|string',
            'floor' => 'nullable|string',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $address = Address::create([
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'area' => $request->area,
            'street' => $request->street,
            'nearBy' => $request->nearBy,
            'additionalDetails' => $request->additionalDetails,
            'floor' => $request->floor,
            'customer_id' => $request->customer_id,
        ]);

        return response()->json(['message' => 'Address created successfully', 'address' => $address], 201);
    }

    public function show($userId)
{
    // استرجاع كل العناوين المتعلقة بالمستخدم المحدد باستخدام الـ $userId
    $addresses = Address::where('customer_id', $userId)->get();

    if ($addresses->isEmpty()) {
        return response()->json(['message' => 'No addresses found for this user.'], 404);
    }

    return response()->json(['addresses' => $addresses], 200);
}


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
 

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
