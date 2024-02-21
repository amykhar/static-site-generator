<?php

namespace App\Exception;

use Exception;

class MissingMetadataException extends Exception
{
    public function __construct(string $message = 'The metadata is missing from the file')
    {
        parent::__construct($message);
    }
}
