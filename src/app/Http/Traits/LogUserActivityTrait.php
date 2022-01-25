<?php

namespace App\Http\Traits;

use App\Models\LogUserActivity;

/**
 * Save log activity by query & mutation.
 */
trait LogUserActivityTrait
{
    public function saveLogActivity($request)
    {
        $doLogging = true;
        if ($request['device'] == 'mobile') {
            $request['action'] = str_replace('"', '', $request['action']);
            //identify request['action'] is query / mutation
            $methodTemp  = ltrim($request['action']);
            $methodTemp  = strtok($methodTemp, " ");
            $method      = (trim($methodTemp) == '{') ? 'query' : $methodTemp;
            //identify field in schema
            $action = explode('{', $request['action']);
            $action = explode('(', (($methodTemp == '{') ? $action[0] : $action[1]));
            $action = trim($action[0]);
            //join word into a single string
            $request['action'] = json_encode([
                'method' => $method,
                'action' => $action,
            ]);
            if ($action == '__schema') {
                $doLogging = false;
            }
        }
        if ($doLogging) {
            $log            = new LogUserActivity();
            $log->people_id = $request['people_id'];
            $log->device    = $request['device'];
            $log->query     = $request['action'];
            $log->save();

            return $log;
        }

        return false;
    }
}
