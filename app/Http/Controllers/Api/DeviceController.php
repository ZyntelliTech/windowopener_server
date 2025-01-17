<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Utils\ResponseUtil;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function get(Request $request) {
        $devices = Device::with('user')->get();
        return response()->json(['data' => $devices]);
    }

    public function getDevices(Request $request) {
        try {
            $user_id = auth()->user()->id;
            $devices = Device::where('user_id',$user_id)->get();
            return response()->json(['data' => $devices->makeHidden(['created_at', 'updated_at', 'type', 'creator', 'user_id', 'location', 'country_id', 'state_id', 'city_id'])]);
            
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('DeviceController getDevices method : ',$th->getTrace());
            return ResponseUtil::failedResponse('Access Denied!',403);
        }
    }

    public function getDeviceByApp($id) {
        $device = Device::find($id);
        return response()->json(['data' => $device->makeHidden(['created_at', 'updated_at', 'type', 'creator', 'user_id', 'location', 'country_id', 'state_id', 'city_id'])]);
    }

    public function store(Request $request)
    {
        $attrs = $request->validate([
            'alias' => ['nullable', 'string'],
            'device_address' => ['required', 'string'],
            'type' => ['nullable', 'integer'],
            'location' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'integer'],
            'is_temp_include' => ['nullable', 'boolean'],
            'is_hum_include' => ['nullable', 'boolean'],
        ]);
        
        foreach($attrs as $key => $value){
            if($value === null) unset($attrs[$key]);
        }
        
        $attrs['user_id'] = auth()->user()->id;
        try {
            return Device::create($attrs);
            
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('DeviceController store method ',$th->getTrace());
            $msg = strpos($th->getMessage(),'Duplicate entry')?"Device Address exists":$th->getMessage();
            return ResponseUtil::failedResponse($msg );
        }   
    }

    public function update(Device $device, Request $request)
    {
        try {
        $attrs = $request->validate([
           'alias' => ['sometimes', 'string'],
           'device_address' => ['sometimes', 'string'],
           'type' => ['sometimes', 'integer'],
           'location' => ['sometimes', 'string'],
           'user_id' => ['sometimes', 'exists:users,id'],
           'status' => ['sometimes', 'integer'],
           'is_temp_include' => ['sometimes', 'boolean'],
           'is_hum_include' => ['sometimes', 'boolean'],
        ]);

        foreach($attrs as $key => $value){
            if($value === null) unset($attrs[$key]);
        }

            $device->update($attrs);
    
            return $device;
            
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('DeviceController update method ',$th->getTrace());
            $msg='Update failed';
            if($th->getCode()==500)$msg='Update Failed';
            return ResponseUtil::failedResponse($msg);
        }
    }

    public function delete($id) {
        Device::destroy($id);
        DeviceLog::where('device_id', $id)->delete();
        return response()->json(['data' => $id]);
    }
}
