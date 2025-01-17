<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingsResource;
use App\Http\Utils\ResponseUtil;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $settings = $user->settings;

        if($settings){
            return SettingsResource::make($settings);
        }

        return response()->json(['message' => 'Settings not Found!'], 404);
    }

    public function update(Request $request){
        $attrs = $request->validate([
            'is_auto' => ['nullable', 'boolean'],
            'low_temperature' => ['nullable', 'numeric'],
            'high_temperature' => ['nullable', 'numeric'],
        ]);

        if(isset($attrs['low_temperature']) && !isset($attrs['high_temperature']) ||
        (!isset($attrs['low_temperature']) && isset($attrs['high_temperature']))){
            return ResponseUtil::failedResponse('Low temperature & high temperature both need to fillup!');
        }
        
        if(isset($attrs['low_temperature']) && isset($attrs['high_temperature'])){
            if($attrs['low_temperature'] > $attrs['high_temperature']){
                return ResponseUtil::failedResponse('Low Temperature cannot be higher than High Temperature');
            }
        }

        $user = auth()->user();
        foreach($attrs as $key => $value){
            if($value === null) unset($attrs[$key]);
        }
        $user = User::find($user->id);
        $settings = Setting::where('user_id',$user->id)->first();
        $settings->fill($attrs);
        $settings->save();
        $settings = $user->settings;

        return SettingsResource::make($settings);
    }
}
