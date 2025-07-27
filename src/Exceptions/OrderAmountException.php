<?php

namespace Rahat1994\SparkcommerceRestRoutes\Exceptions;

use Exception;

class OrderAmountException extends Exception
{
    public function __construct($message = 'Order amount not met', $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
