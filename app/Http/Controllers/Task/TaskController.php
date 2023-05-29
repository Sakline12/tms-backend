<?php

namespace App\Http\Controllers\Task;

use App\Models\Tas;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\TaskAssign;
use Illuminate\Http\Request;
use App\Models\ProjectAssign;
use Ramsey\Uuid\Type\Integer;
use App\Models\TaskAttachment;
use Illuminate\Support\Carbon;
use PhpParser\Node\Expr\Assign;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function task_create(Request $request)
    {
        $validator = validator::make($request->all(), [
            // 'project_id' => 'required',
            // 'name' => 'required',
            // 'description' => 'required',
            // 'end_date' => 'required',

        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 403);
        }


        $id = $request->user()->id;

        // $find_project=Project::find( $request->project_id)->task;
        // return response()->json([
        // $find_project
        // ]);

        $find_project = Project::with('task')->find($request->project_id);

        $tt = $find_project->task;

        foreach ($tt as $key => $value) {
            if ($value->name == $request->name) {
                return response()->json("Task Already Assisgn");
            }
        }

        $project = new Task();
        $project->user_id = $id;
        $project->project_id = $request->project_id;
        $project->name = $request->name;
        $project->description = $request->description;
        //$project->status = $request->status;
        $project->start_date = $request->start_date;
        $project->end_date = $request->end_date;
        $project->save();

        $data = [
            'status' => true,
            'message' => 'Task already created',
            'data' => $project,
        ];

        return response()->json($data, 201);
    }

    public function task_assigned(Request $request)
    {
        $user_ids = $request->input('user_ids');
        $task_id = $request->task_id;

        $assignedTasks = [];

        foreach ($user_ids as $user_id) {
            $user = User::find($user_id);

            if (!$user || !$user->isActive) {
                return response()->json(["message" => "User is not active"]);
            }

            $existingTaskAssign = TaskAssign::where('user_id', $user_id)->where('task_id', $task_id)->first();

            if ($existingTaskAssign) {
                $assignedTasks[] = [
                    'task_name' => $existingTaskAssign->task->name,
                    'user_name' => $existingTaskAssign->user->first_name . ' ' . $existingTaskAssign->user->last_name
                ];
            } else {
                $task_assign = new TaskAssign();
                $task_assign->user_id = $user_id;
                $task_assign->task_id = $task_id;
                $task_assign->date = $request->date;
                $task_assign->save();
                $assignedTasks[] = [
                    'task_name' => $task_assign->task->name,
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



    public function file_create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
            'file' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 403);
        }



        $imageName = "";
        if ($image = $request->file('file')) {
            $imageName = time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $imageName);
        } else {
            $imageName = Null;
        }

        $id = $request->user()->id;
        $files = new TaskAttachment();
        $files->user_id = $id;
        $files->file = $imageName;
        $files->task_id = $request->task_id;
        $files->save();

        $data = [
            'status' => true,
            'message' => 'File uploaded successfully',
            'data' => $files,
        ];

        return response()->json($data, 200);
    }


    public function delete_file(Request $request, $file_id)
    {
        $file = TaskAttachment::find($file_id);

        if ($file) {
            $filePath = public_path('images/' . $file->file);
            if (file_exists($filePath)) {
                unlink($filePath);

                $file->delete();

                $data = [
                    'status' => true,
                    'message' => 'This file is successfully deleted',
                    'data' => []
                ];

                return response()->json($data, 200);
            } else {
                $data = [
                    'status' => false,
                    'message' => 'File not found',
                    'data' => []
                ];

                return response()->json($data, 404);
            }
        } else {
            $data = [
                'status' => false,
                'message' => 'This file is not found or already deleted',
                'data' => []
            ];

            return response()->json($data, 404);
        }
    }


    public function task_update(Request $request)
    {
        $id = $request->id;
        $task = Task::find($id);
        if ($task) {
            $task->project_id = $request->project_id;
            $task->name = $request->name;
            $task->description = $request->description;
            $task->status = $request->status;
            $task->start_date = $request->start_date;
            $task->end_date = $request->end_date;
            $task->save();

            $data = [
                "status" => true,
                "message" => 'The task is successfull Updated',
                "data" => $task
            ];
            return response()->json($data, 200);
        }
    }

    public function task_delete(Request $request, $id)
    {
        $project = Task::where('id', $id)->delete();
        if ($project) {
            return response()->json([
                'message' => 'This task is successfull deleted',
                'status' => true,

            ], 200);
        } else {
            return response()->json([
                'message' => 'This task is not deleted',
                'status' => false,
            ], 200);
        }
    }

    public function task_assigned_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required',
            'task_id' => 'required',
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
        $task_assign = TaskAssign::find($id);
        $task_assign->user_id = $user_id;
        $task_assign->task_id = $request->task_id;
        $task_assign->date = $request->date;
        $task_assign->save();


        $assignedProjects[] = $task_assign;
        foreach ($assignedProjects as $user) {
            $assignedUsers[] = [
                'Task Name' => $task_assign->task->name,
                'User name' => $user->user->first_name . ' ' . $user->user->last_name
            ];
        }

        $data = [
            'status' => true,
            'message' => "Assigned are:",
            'data' => $assignedUsers
        ];

        return response()->json($data, 200);
    }

    public function list_task()
    {
        $task = Task::with('project', 'user')->get();
        if ($task) {
            $data = [
                "status" => true,
                "message" => "Here are all tasks",
                "data" => $task
            ];
            return response()->json($data);
        } else {
            $data = [
                "status" => false,
                "message" => "Task not found",
                "data" => []
            ];
            return response()->json($data);
        }
    }

    public function task_with_project()
    {
        $task = Task::with('project')->get();
        $data = [
            'status' => true,
            'message' => 'Tasks with projects:',
            'data' => $task,
        ];
        return response()->json($data);
    }

    public function task_count()
    {
        $total_task = Task::count();
        $complete_task = Task::where('status', 'Finished')->count();
        $incompleted_task = Task::where('status', 'Pending')->count();
        $overdue_task = Task::where('status', 'Onhold')->count();

        $data = [
            'status' => true,
            'message' => 'Here are all counts',
            'data' => [
                'Total task' => $total_task,
                'Complete task' => $complete_task,
                'In-completed task' => $incompleted_task,
                'Overdue task' => $overdue_task
            ]
        ];
        return response()->json($data, 201);
    }

    public function task_for_specific_project(Request $request, $id)
    {
        $project = Project::with('task.TaskAssign.user')->find($id);
        $data = [
            'status' => true,
            'message' => 'Tasks for specific projects:',
            'data' => $project
        ];
        return response()->json($data);
    }

    public function calculate_task(Request $request)
    {
        $projects = Project::with('task')->with('user.designation')->get();
        $projectData = [];

        foreach ($projects as $project) {
            $completed_tasks = $project->task->where('status', 'Finished')->count();
            $total_tasks = $project->task->count();

            if ($total_tasks > 0) {
                $task_complete = ($completed_tasks / $total_tasks) * 100;
            } else {
                $task_complete = 0;
            }

            $projectData[] = [
                'project' => $project,
                'task complete' => Round($task_complete, 2),
            ];
        }

        $data = [
            'status' => true,
            'message' => 'Projects with tasks:',
            'data' => $projectData,
        ];

        return response()->json($data, 201);
    }

    public function today_task()
    {
        $today = Carbon::now()->format('Y-m-d');
        $today_task = Task::where('start_date', $today)->get();
        if ($today_task) {
            $data = [
                'status' => true,
                'message' => '....Todays tasks are....',
                'data' => $today_task
            ];
            return response()->json($data, 200);
        } else {
            $data = [
                'status' => false,
                'message' => 'Task not found',
                'data' => []
            ];
            return response()->json($data, 404);
        }
    }

    public function remain_days(Request $request)
    {
        $today = Carbon::now()->format('Y-m-d');

        $projects = Project::all()
            ->where('status', '!=', 'Finished')
            ->where('end_date', "<", $today);

        $projectData = [];
        foreach ($projects as $project) {
            $end_date = $project->end_date;
            $end_date = Carbon::parse($end_date);
            $project_delays = $end_date->diffInDays($today);
            $user = User::find($project->user_id);
            $projectData[] = [
                'id' => $project->id,
                'name' => $project->name,
                'user_id' => $user->first_name . " " . $user->last_name,
                'project_delays' => $project_delays,
            ];
        }

        $data = [
            'status' => true,
            'message' => 'projects delays are:',
            "data" => $projectData
        ];

        return response()->json($data, 201);
    }

    public function search_Task($id)
    {
        $task = Task::with('project', 'user')->find($id);
        return response()->json($task);
    }

    public function task_with_taskassign_and_user($id)
    {
        $task = Task::with(['TaskAssign.user'])->find($id);
        if ($task) {
            $taskAssign = $task->TaskAssign->map(function ($assign) {
                return [
                    'id' => $assign->id,
                    'user' => $assign->user->first_name . ' ' . $assign->user->last_name,
                    'date' => $assign->date,
                    'image' => $assign->user->image

                ];
            });
            $data = [
                'status' => true,
                'message' => 'Here are task details',
                'data' => [
                    'id' => $task->id,
                    'user_id' => $task->user_id,
                    'project_id' => $task->project_id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status,
                    'start_date' => $task->start_date,
                    'end_date' => $task->end_date,

                    'task_assign' => $taskAssign,
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
                        'image' => $task->user->image,
                        'created_at' => $task->user->created_at,
                        'updated_at' => $task->user->updated_at,
                    ],
                ],
            ];
            return response()->json($data, 201);
        }
    }

    public function images_for_task(Request $request, $task_id)
    {
        $task = Task::find($task_id)->taskattachment;

        $data = [
            'status' => true,
            'message' => "Task with images:",
            'data' => $task
        ];

        return response()->json($data, 200);
    }

    public function comments_for_task(Request $request, $task_id)
    {
        $task = Comment::join('users', 'comments.user_id', '=', 'users.id')
            ->select('comments.id', User::raw('CONCAT(users.first_name, " ", users.last_name) as user_name'), 'comments.task_id', 'comments.comment_box', 'comments.date', User::raw('CONCAT(users.image) as image'),)
            ->where('comments.task_id', $task_id)
            ->get();

        $data = [
            'status' => true,
            'message' => "Tasks comment:",
            'data' => $task
        ];

        return response()->json($data, 200);
    }

    public function task_assign_image(Request $request, $id)
    {
        $task = TaskAssign::where('task_id', $id)->with('user')->get();
        // $user = $task->user()->get();
        $data = [
            'status' => true,
            'message' => "Task assign with images:",
            'data' => $task
        ];

        return response()->json($data, 200);
    }

    public function assigned_my_task(Request $request)
    {

        $id = $request->user()->id;
        $task = TaskAssign::where('user_id', $id)->with('task')->get();
        $data = [
            'status' => true,
            'message' => "My tasks are:",
            'data' => $task
        ];

        return response()->json($data, 200);
    }
}
