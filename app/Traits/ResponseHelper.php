<?php 
namespace App\Traits;

use Illuminate\Http\Request;

trait ResponseHelper{

    public function makeJSONResponse($data, int $code){
        return response()->json([$data],$code); exit;
    }
}
