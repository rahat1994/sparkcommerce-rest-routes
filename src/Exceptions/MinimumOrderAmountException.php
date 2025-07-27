<?php

namespace Rahat1994\SparkcommerceRestRoutes\Exceptions;

use Exception;
use Rahat1994\SparkcommerceRestRoutes\Exceptions\OrderAmountException;

class MinimumOrderAmountException extends OrderAmountException
{
    public function __construct($message = 'Minimum order amount not met', $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
