<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;

class OrderController extends SCBaseController
{
    public $recordModel = SCOrder::class;

    public function index(Request $request)
    {
        // Your code here
        $user = Auth::guard('sanctum')->user();

        $params = [
            'user_id' => $user->id,
            'limit' => 10,
            'order_by' => 'created_at',
            'order' => 'desc',
        ];

        $data = $this->callHook('orderIndexParams', $params);

        $params = $data ?? $params;

        $orders = $this->recordModel::limit($params['limit'])
            ->where('user_id', $params['user_id'])
            ->orderBy($params['order_by'], $params['order'])
            ->paginate();

        $data = $this->callHook('afterOrderListFetch', $orders);

        $orders = $data ?? $orders;

        return SCOrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $trackingNumber)
    {
        $user = Auth::guard('sanctum')->user();

        try {

            $params = [
                'tracking_number' => $trackingNumber,
            ];

            $data = $this->callHook('singlerderParams', $params);

            $params = $data ?? $params;

            $order = SCOrder::where('tracking_number', $params['tracking_number'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $data = $this->callHook('afterSingleOrderFetch', $order);

            $order = $data ?? $order;

            return SCOrderResource::make($order);
        } catch (ModelNotFoundException $th) {
            //throw $th;
            return response()->json(
                [
                    'message' => 'resource not found',
                    'tracking_number' => $trackingNumber,
                ],
                404
            );
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(
                [
                    'message' => 'Something went wrong',
                ],
                400
            );
        }
    }
}
