<?php

namespace Rahat1994\SparkcommerceRestRoutes\Exceptions;

use Exception;

class InvalidCouponException extends Exception
{
    public function __construct($message = 'Invalid coupon provided', $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
