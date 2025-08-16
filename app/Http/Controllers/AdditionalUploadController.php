<?php

namespace App\Http\Controllers;

use App\Models\AdditionalUpload;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreAdditionalUploadRequest;
use App\Http\Requests\UpdateAdditionalUploadRequest;

class AdditionalUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $additionalUploads = AdditionalUpload::all();
        return response()->json($additionalUploads);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdditionalUploadRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['file_path'] = $request->file('file_path')->store('additional_uploads', 'public');

        $additionalUpload = AdditionalUpload::create($validatedData);
        return response()->json([
            'message' => 'Additional upload created successfully.',
            'additional_upload' => $additionalUpload
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdditionalUpload $additionalUpload)
    {
        return response()->json($additionalUpload);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdditionalUploadRequest $request, AdditionalUpload $additionalUpload)
    {
        $validatedData = $request->validated();

        // Handle file upload
        if ($request->hasFile('file_path')) {
            // Delete the old file if it exists
            if ($additionalUpload->file_path) {
                Storage::disk('public')->delete($additionalUpload->file_path);
            }
            $validatedData['file_path'] = $request->file('file_path')->store('additional_uploads', 'public');
        }

        $additionalUpload->update($validatedData);

        return response()->json([
            'message' => 'Additional upload updated successfully.',
            'additional_upload' => $additionalUpload
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdditionalUpload $additionalUpload)
    {
        // Delete the file if it exists
        if ($additionalUpload->file_path) {
            Storage::disk('public')->delete($additionalUpload->file_path);
        }

        $additionalUpload->delete();

        return response()->json([
            'message' => 'Additional upload deleted successfully.'
        ]);
    }
}
