<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;

class AdminFaqController extends Controller
{
    /**
     * Display a listing of all FAQs (admin can see all).
     */
    public function index()
    {
        $faqs = Faq::orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $faqs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFaqRequest $request)
    {
        $validatedData = $request->validated();
        
        $faq = Faq::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully.',
            'data' => $faq
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Faq $faq)
    {
        return response()->json([
            'success' => true,
            'data' => $faq
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $validatedData = $request->validated();
        $faq->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully.',
            'data' => $faq
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully.'
        ]);
    }
}