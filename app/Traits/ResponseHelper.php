<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait ResponseHelper
{

    public function makeJSONResponse($data, int $code)
    {
        return response()->json($data, $code);
        exit;
    }

    public function customErrorMessage()
    {
        return [
            'school_id.required'    => 'School id is required.',
            'school_id.exists'      => 'School id is invalid.',
            'school_id.numeric'      => 'School id must be a number'
        ];
    }
}
