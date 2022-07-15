<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Identification;
use App\Models\KYC;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PubSub;
use Throwable;

class KYCController extends Controller
{
    /**
     * Display a listing of Pending KYC
     *
     * @OA\Get(
     *     path="/admin/kyc",
     *     description="Get list of KYC",
     *     tags={"Admin | KYC"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="limit",
     *         description="Number of expected data in response",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *              type="integer",
     *              default=20,
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *     )
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 20;
        $kycs = KYC::latest()
            ->where('status', KYC::STATUS_PENDING)->paginate($limit);

        return response()->json([
            'type' => 'success',
            'title' => 'Get KYC',
            'message' => 'Pending Submitted KYCs',
            'data' => $kycs
        ], 200);
    }

    /**
     * Endpoint for Approving OR Rejecting KYC
     *
     * @OA\Put(
     *     path="/admin/kyc/{id}",
     *     description="Response to KYC",
     *     tags={"Admin | KYC"},
     *
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         description="KYC ID",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *              type="string",
     *              default="xxx-yyy-zzz",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="KYC Status APPROVED OR REJECTED",
     *                 example="APPROVED"
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     )
     * )
     *
     * @param Request $request
     * @param string $id
     *
     * @return Response
     */
    public function updateKYC(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'status' => 'required|in:APPROVED,REJECTED',
            ]);

            $kyc = KYC::findOrFail($id);
            $kyc->status = $request->status;
            $kyc->save();

            /**
             * Notify User
             *
             */
            try {
                $user = User::find($kyc->user_id);
                PubSub::publish("KYC" . $request->status, [
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'kyc' => $kyc,
                ], 'mail');
            } catch (Throwable $th) {
            }

            return response()->json([
                'type' => 'success',
                'title' => 'KYC Response',
                'message' => 'KYC Response sent',
                'data' => $kyc,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'type' => 'danger',
                'title' => 'KYC Response',
                'message' => $e->getMessage(),
            ], 500);
        }
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
