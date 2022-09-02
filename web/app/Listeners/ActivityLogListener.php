<?php

namespace App\Listeners;

use App\Models\Activity;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActivityLogListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param array $event
     *
     * @return void
     */
    public function handle(array $data): void
    {
        // validate input
        $validator = Validator::make($data, [
            "title" => "required|string",
            "description" => "required|string",
            "activity_time" => "required|string",
            "user_id" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        // Try to add new activity
        try {
            // Create new
            $activity = Activity::create($data);

            // Return response
            Log::info("Activity Logged");
        } catch (Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
