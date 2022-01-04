<?php

namespace App\Http\Traits;

use App\Models\LogUserActivity;

trait LogUserActivityTrait
{
    public function saveLogActivity($peopleId, $device, $request = null)
    {
        if ($request != null) {
            $request = str_replace('\n', '', json_encode($request->input('query')));
            $request = str_replace('{', '|', $request);
            $request = str_replace('}', '', $request);
            $request = explode('(', $request);
            $request = preg_replace("/[[:blank:]]+/"," ",$request[0]);
            $request = str_replace('"| ', '', $request);
            $request = str_replace('"', '', $request);

        }

        $log            = new LogUserActivity();
        $log->people_id = $peopleId;
        $log->device    = $device;
        $log->query     = $request;
        $log->save();
    }
}
