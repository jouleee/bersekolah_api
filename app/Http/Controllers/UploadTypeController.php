<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadTypeRequest;
use App\Http\Requests\UpdateUploadTypeRequest;
use App\Models\UploadType;

class UploadTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $uploadTypes = UploadType::all();
        return response()->json($uploadTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUploadTypeRequest $request)
    {
        $validatedData = $request->validated();

        $uploadType = UploadType::create($validatedData);

        return response()->json([
            'message' => 'Upload Type created successfully.',
            'uploadType' => $uploadType
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UploadType $uploadType)
    {
        return response()->json($uploadType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUploadTypeRequest $request, UploadType $uploadType)
    {
        $validatedData = $request->validated();

        $uploadType->update($validatedData);

        return response()->json([
            'message' => 'Upload Type updated successfully.',
            'uploadType' => $uploadType
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UploadType $uploadType)
    {
        $uploadType->delete();

        return response()->json([
            'message' => 'Upload Type deleted successfully.'
        ]);
    }
}
