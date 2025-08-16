<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // For public, only show published FAQs
        $faqs = Faq::where('status', 'published')->get();
        return response()->json($faqs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFaqRequest $request)
    {
        $validatedData = $request->validated();
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'draft';
        }
        
        $faq = Faq::create($validatedData);

        return response()->json([
            'message' => 'FAQ created successfully.',
            'faq' => $faq
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Faq $faq)
    {
        // For public, only show if published
        if ($faq->status !== 'published') {
            return response()->json(['message' => 'FAQ not found'], 404);
        }
        return response()->json($faq);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $validatedData = $request->validated();
        $faq->update($validatedData);

        return response()->json([
            'message' => 'FAQ updated successfully.',
            'faq' => $faq
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();
        return response()->json(['message' => 'FAQ deleted successfully.']);
    }
}
