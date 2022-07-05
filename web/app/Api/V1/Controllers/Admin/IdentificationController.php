<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Identification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IdentificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return response([
            'data' => 'Okay'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Identification $identification
     * @return Response
     */
    public function show(Identification $identification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Identification $identification
     * @return Response
     */
    public function update(Request $request, Identification $identification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Identification $identification
     * @return Response
     */
    public function destroy(Identification $identification)
    {
        //
    }
}
