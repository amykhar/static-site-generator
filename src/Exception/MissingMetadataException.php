<?php

namespace App\Exception;

use Exception;

class MissingMetadataException extends Exception
{
    private const MESSAGE = 'The metadata is missing from the file.';
    public function __construct(string $message)
    {
        $message = self::MESSAGE . ' ' . $message;
        parent::__construct($message);
    }
}
