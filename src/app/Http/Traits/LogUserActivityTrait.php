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
            //identify request['action'] is query / mutation (graphql)
            $methodTemp  = ltrim($request['action']);
            $methodTemp  = strtok($methodTemp, " ");
            $method = (str_contains($methodTemp, 'mutation')) ? 'mutation' : 'query';
            //identify field in schema
            $action = explode('{', $request['action']);
            $action = explode('(', (($methodTemp == '{') ? $action[0] : $action[1]));
            $action = trim($action[0]);
            $action = str_replace('__typename\n  ', '', $action); //handle for mobile
            $request['type']    = $method;
            $request['action']  = $action;
            if ($action == '__schema') { //handle for playground graphql
                $doLogging = false;
            }
        }
        if ($doLogging) {
            $log            = new LogUserActivity();
            $log->people_id = $request['people_id'];
            $log->device    = $request['device'];
            $log->type     = $request['type'] ?? null;
            $log->action    = $request['action'];
            $log->save();
            return $log;
        }
        return false;
    }
}
