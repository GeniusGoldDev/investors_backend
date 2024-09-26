<?php
 
namespace App\Http\Traits;

use Illuminate\Http\Exceptions\HttpResponseException;

trait ApiResponseTrait {
    
    /**
     * 
     * returns an empty object if data is null
     *
     * @param Object $data
     * @return  Object  $data
     */
    private function data($data){
        return $data === null ? $data = new \stdClass() : $data;
    }
    
    /**
     * 
     * return a success message along with data
     *
     * @param String $message
     * @param Object $data
     * @return \Illuminate\Http\Response
     */
    public function successResponse($message, $data = null,$status=200){
        return response([
            'status'    => true,
            'message'   => $message,
            'data'      => $this->data($data)
        ], $status);
    }

    /**
     * 
     * return a failure message along with data
     *
     * @param String $message
     * @param Object $data
     * @return \Illuminate\Http\Response
     */
    public function failureResponse($message, $data = null, $status=400){
        throw new HttpResponseException(
            response([
                'status' => false,
                'message' => $message,
                'data' => $this->data($data)
            ], $status));
    }
    
    

 
}