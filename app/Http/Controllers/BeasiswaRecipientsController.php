<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeasiswaRecipientsRequest;
use App\Http\Requests\UpdateBeasiswaRecipientsRequest;
use App\Models\BeasiswaRecipients;

class BeasiswaRecipientsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $beasiswaRecipients = BeasiswaRecipients::all();
        return response()->json($beasiswaRecipients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBeasiswaRecipientsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BeasiswaRecipients $beasiswaRecipients)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBeasiswaRecipientsRequest $request, BeasiswaRecipients $beasiswaRecipients)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BeasiswaRecipients $beasiswaRecipients)
    {
        //
    }
}
