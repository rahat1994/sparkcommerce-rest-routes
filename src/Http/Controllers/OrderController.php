<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;

class OrderController extends SCBaseController
{
    public $recordModel = SCOrder::class;

    public function index(Request $request)
    {
        $request->validate([
            'item_count' => 'nullable|integer',
        ]);
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
            ->orderBy($params['order_by'], $params['order']);

        $builder = $this->callHook('orderIndexQueryBuilder', $orders);
        
        $orders = $builder ? $builder : $orders;
        
        $orders = $orders->paginate($request->item_count);

        $data = $this->callHook('afterOrderListFetch', $orders);

        $orders = $data ?? $orders;

        return $this->resourceCollection($orders);
    }

    public function show(Request $request, $trackingNumber)
    {
        $validatedData = Validator::make(
            ['tracking_number' => $trackingNumber],
            ['tracking_number' => 'required|string']
        )->validate();

        $user = Auth::guard('sanctum')->user();
        try {

            $params = [
                'tracking_number' => $validatedData['tracking_number'],
            ];

            $data = $this->callHook('singleOrderParams', $params);

            $params = $data ?? $params;

            $order = $this->recordModel::where('tracking_number', $params['tracking_number'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $data = $this->callHook('afterSingleOrderFetch', $order);

            $order = $data ?? $order;

            return $this->singleModelResource($order);
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
