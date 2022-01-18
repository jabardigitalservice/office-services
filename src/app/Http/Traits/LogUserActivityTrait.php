<?php

namespace App\Http\Traits;

use App\Models\LogUserActivity;

/**
 * Save log activity by query & mutation.
 */
trait LogUserActivityTrait
{
    public function saveLogActivity($peopleId, $device, $request = null)
    {
        if ($request != null) {
            $request = str_replace('"', '', $request->input('query'));
            //identify request is query / mutation
            $firstWordTemp  = ltrim($request);
            $firstWordTemp  = strtok($firstWordTemp, " ");
            $firstWord      = (trim($firstWordTemp) == '{') ? 'query' : $firstWordTemp;
            //identify field in schema
            $secondWord = explode('{', $request);
            $secondWord = explode('(', (($firstWordTemp == '{') ? $secondWord[0] : $secondWord[1]));
            $secondWord = ltrim($secondWord[0]);
            //join word into a single string
            $request = $firstWord . ' | ' . $secondWord;
        }

        $log            = new LogUserActivity();
        $log->people_id = $peopleId;
        $log->device    = $device;
        $log->query     = $request;
        $log->save();

        return $log;
    }
}
