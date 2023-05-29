<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function event_create(Request $request)
    {

        $validator = validator::make($request->all(), [
            'event_name' => 'required|unique:events,event_name',
            'start' => 'required',
            'end' => 'required',
            'status' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 403);
        }

        $event = new Event();
        $event->event_name = $request->event_name;
        $event->start = $request->start;
        $event->end = $request->end;
        $event->status = $request->status;
        $event->save();

        if ($event) {
            $data = [
                'status' => true,
                'message' => 'Event Created Successfully',
                'data' => $event
            ];

            return response()->json(
                $data,
                201
            );
        }
    }

    public function list_of_event(Request $request)
    {
        $event = Event::all();
        if ($event) {
            $data = [
                'status' => true,
                'message' => 'List of events are:',
                'data' => $event
            ];
            return response()->json($data, 200);
        } else {
            $data = [
                'status' => false,
                'message' => 'No found task',
            ];
            return response()->json($data, 500);
        }
    }

    public function event_delete(Request $request, $id)
    {
        $project = Event::where('id', $id)->delete();

        $data = [
            'message' => 'This event is deleted',
            'status' => true
        ];

        return response()->json($data, 200);
    }
}
