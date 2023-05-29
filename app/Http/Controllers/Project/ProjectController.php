<?php

namespace App\Http\Controllers\Project;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\ProjectAssign;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function create_project(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'name' => 'required',
            'supervisor' => 'required',
            'remarks' => 'required',
            'end_date' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 403);
        }

        $id = $request->user()->id;
        $client_id = $request->client_id;
        $client = Client::find($client_id);

        if (!$client || !$client->isActive) {
            $data = [
                'status' => false,
                'message' => 'Client is not active',
                'data' => []
            ];
            return response()->json($data, 404);
        }

        $find_project = Client::with('project')->find($client_id);
        $find_project = $find_project->project;
        foreach ($find_project as $key => $value) {
            if ($value->name == $request->name) {
                $data = [
                    'status' => false,
                    'message' => 'Client of task Already Assigned',
                    'data' => []
                ];
                return response()->json($data, 404);
            }
        }

        $project = new Project();
        $project->user_id = $id;
        $project->client_id = $client_id;
        $project->name = $request->name;
        $project->description = $request->description;
        $project->supervisor = $request->supervisor;
        $project->remarks = $request->remarks;
        $project->start_date = $request->start_date;
        $project->end_date = $request->end_date;
        $project->save();

        $data = [
            'status' => true,
            'message' => 'Project successfully created',
            'data' => $project
        ];

        return response()->json($data, 201);
    }

    public function project_assigned(Request $request)
    {
        $user_ids = $request->input('user_ids');
        $project_id = $request->project_id;

        $assignedTasks = [];

        foreach ($user_ids as $user_id) {
            $user = User::find($user_id);

            if (!$user || !$user->isActive) {
                return response()->json(["message" => "User is not active"]);
            }

            $existingTaskAssign = ProjectAssign::where('user_id', $user_id)->where('project_id', $project_id)->first();

            if ($existingTaskAssign) {
                $assignedTasks[] = [
                    'task_name' => $existingTaskAssign->project->name,
                    'user_name' => $existingTaskAssign->user->first_name . ' ' . $existingTaskAssign->user->last_name
                ];
            } else {
                $task_assign = new ProjectAssign();
                $task_assign->user_id = $user_id;
                $task_assign->project_id = $project_id;
                $task_assign->date = $request->date;
                $task_assign->save();
                $assignedTasks[] = [
                    'task_name' => $task_assign->project->name,
                    'user_name' => $task_assign->user->first_name . ' ' . $task_assign->user->last_name
                ];
            }
        }

        $data = [
            'status' => true,
            'message' => "Tasks are:",
            'data' => $assignedTasks
        ];

        return response()->json($data, 201);
    }


    public function project_list()
    {
        $projects = Project::with('client')->with('user')->get();
        if ($projects) {
            $data = [
                "status" => true,
                "message" => "Here are all projects",
                "data" => $projects
            ];
            return response()->json($data);
        } else {
            $data = [
                "status" => false,
                "message" => "Project not found",
                "data" => []
            ];
            return response()->json($data);
        }
    }

    public function project_update(Request $request)
    {
        $id = $request->id;
        $project = Project::find($id);
        if ($project) {
            $project->client_id = $request->client_id;
            $project->name = $request->name;
            $project->description = $request->description;
            $project->status = $request->status;
            $project->supervisor = $request->supervisor;
            $project->remarks = $request->remarks;
            $project->start_date = $request->start_date;
            $project->end_date = $request->end_date;
            $project->save();

            $data = [
                "status" => true,
                "message" => 'The department is successfull Updated',
                "data" => $project
            ];
            return response()->json($data, 200);
        }
    }

    public function project_delete(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $project = $project->delete();
        if ($project) {
            $data = [
                'status' => true,
                'message' => 'Project is deleted',
                'data' => []
            ];
            return response()->json($data);
        }
    }

    public function project_with_task()
    {
        $project = Project::with('task')->get();
        $data = [
            'status' => true,
            'message' => 'Projects with tasks:',
            'data' => $project,
        ];
        return response()->json($data, 201);
    }

    public function project_assigned_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required',
            'project_id' => 'required',
            'date' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 403);
        }

        $user_ids = $request->input('user_ids');
        foreach ($user_ids as $user_id) {
            $user = User::find($user_id);
            if (!$user || !$user->isActive) {
                return response()->json(["message" => "User is not active"]);
            }
        }

        $id = $request->id;
        $project_assign = ProjectAssign::find($id);
        $project_assign->user_id = $user_id;
        $project_assign->project_id = $request->project_id;
        $project_assign->date = $request->date;
        $project_assign->save();


        $assignedProjects[] = $project_assign;
        foreach ($assignedProjects as $user) {
            $assignedUsers[] = [
                'project_name' => $project_assign->project->name,
                'user_name' => $user->user->first_name . ' ' . $user->user->last_name
            ];
        }

        $data = [
            'status' => true,
            'message' => "Assigned are:",
            'data' => $assignedUsers
        ];

        return response()->json($data, 200);
    }

    public function project_for_specific_user(Request $request)
    {
        $user_id = $request->user_id;
        $project = User::find($user_id)->project;
        $data = [
            'status' => true,
            'message' => 'Project for the user:',
            'data' => $project
        ];
        return response()->json($data, 201);
    }


    public function project_assigns_users(Request $request)
    {
        $project_id = $request->project_id;
        $project = Project::find($project_id)->ProjectAssign;

        $data = [
            'status' => true,
            'message' => 'Users are only assigned for projects',
            'data' => [
                $project

            ]
        ];
        return response()->json($data);
    }

    public function user_project(Request $request)
    {
        $user_id = $request->user()->id;

        $projects = ProjectAssign::where('user_id', $user_id)->with('project')->get();

        return response()->json($projects);
    }


    public function today_project(Request $request)
    {
        $today = Carbon::now()->format('Y-m-d');
        $today_project = Project::where('start_date', $today)->get();
        if ($today_project) {
            $data = [
                'status' => true,
                'message' => '.....Here are all todays tasks....',
                'data' => $today_project
            ];

            return response()->json($data, 200);
        } else {
            $data = [
                'status' => false,
                'message' => '.....Todays tasks not found....',
                'data' => []
            ];

            return response()->json($data, 404);
        }
    }

    public function project_with_project_assign_and_user($id)
    {
        $task = Project::with(['ProjectAssign.user'])->find($id);
        if ($task) {
            $taskAssign = $task->ProjectAssign->map(function ($assign) {
                return [
                    'id' => $assign->id,
                    'user' => $assign->user->first_name . ' ' . $assign->user->last_name,
                    'date' => $assign->date,
                    'image' => $assign->user->image

                ];
            });
            $data = [
                'status' => true,
                'message' => 'Here are project details',
                'data' => [
                    'id' => $task->id,
                    'user_id' => $task->user_id,
                    'project_id' => $task->project_id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status,
                    'start_date' => $task->start_date,
                    'end_date' => $task->end_date,

                    'project_assign' => $taskAssign,
                    'user' => [
                        'id' => $task->user->id,
                        'name' => $task->user->first_name . ' ' . $task->user->last_name,
                        'email' => $task->user->email,
                        'address' => $task->user->address,
                        'phone' => $task->user->phone,
                        'designation_id' => $task->user->designation_id,
                        'department_id' => $task->user->department_id,
                        'type' => $task->user->type,
                        'isActive' => $task->user->isActive,
                        'image' => $task->user->image
                    ],
                ],
            ];
            return response()->json($data, 201);
        }
    }

    public function assigned_my_project(Request $request)
    {

        $id = $request->user()->id;
        $task = ProjectAssign::where('user_id', $id)->with('project')->get();
        $data = [
            'status' => true,
            'message' => "My projects are:",
            'data' => $task
        ];

        return response()->json($data, 200);
    }
}
