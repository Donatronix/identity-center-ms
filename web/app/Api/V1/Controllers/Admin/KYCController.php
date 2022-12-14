<?php

namespace App\Api\V1\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        try {
            $status = $request->status ?? 'pending';
            $statuses = KYC::$statuses;
            $statuses[] = 'all';

            if (!in_array($status, $statuses)) {
                return response()->jsonApi([
                    'title' => 'Get KYC',
                    'message' => 'KYC status is not supported',
                ], 400);
            }

            if (strtolower($status) == 'all') {
                $kycs = KYC::latest()
                    ->paginate($request->get('limit', config('settings.pagination_limit')));
            }
            else {
                $kycs = KYC::latest()
                    ->where('status', $status)
                    ->paginate($request->get('limit', config('settings.pagination_limit')));
            }

            return response()->jsonApi([
                'title' => 'Get list of user KYC requests',
                'message' => ucfirst($status) . ' Submitted KYCs',
                'data' => $kycs->toArray()
            ]);
        }
        catch (Exception $e){
            return response()->jsonApi([
                'title' => 'Get list of user KYC requests',
                'message' => $e->getMessage(),
            ], 404);
        }
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
     *             )
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
    public function update(Request $request, $id)
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
                ], config('pubsub.queue.communications'));
            } catch (Throwable $th) {
            }

            return response()->jsonApi([
                'title' => 'KYC Response',
                'message' => 'KYC Response sent',
                'data' => $kyc,
            ]);
        } catch (Exception $e) {
            return response()->jsonApi([
                'title' => 'KYC Response',
                'message' => $e->getMessage(),
            ], 500);
        }
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
