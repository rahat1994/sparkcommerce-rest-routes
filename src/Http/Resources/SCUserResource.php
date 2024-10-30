<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SCUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'subscription_type' => $this->subscription_type,
            'subscription_expiration_date' => $this->subscription_expiration_date,
            'subscription_creation_date' => $this->subscription_creation_date,
            'fcm_key' => $this->fcm_key,
            'user_type' => $this->user_type,
            'is_premium_b2b' => $this->is_premium_b2b,
            'is_premium_b2c' => $this->is_premium_b2c,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'id' => $this->id,
        ];

        // $table->string('subscriber_type')->nullable();
        // $table->string('subscription_expiration_date')->nullable();
        // $table->string('subscription_creation_date')->nullable();
        // $table->string('fcm_key')->nullable();
        // $table->string('user_type')->nullable();
        // $table->boolean('is_premium_b2b')->nullable();
        // $table->boolean('is_premium_b2c')->nullable();
    }
}
