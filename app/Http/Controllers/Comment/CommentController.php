<?php

namespace App\Http\Controllers\Comment;

use App\Models\Task;
use App\Models\User;
use App\Models\Comment;
use App\Models\TaskAssign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;

class CommentController extends Controller
{
    public function create_comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
            'comment_box' => 'required',
            'date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->user()->id;
        $taskId = $request->task_id;
        $commentText = $request->comment_box;


        $existingComment = Comment::where('task_id', $taskId)
            ->where('comment_box', $commentText)
            ->first();

        if ($existingComment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment already exists for this task'
            ], 422);
        }

        $comment = new Comment();
        $comment->user_id = $userId;
        $comment->task_id = $taskId;
        $comment->comment_box = $commentText;
        $comment->date = $request->date;
        $comment->save();

        $data = [
            'status' => true,
            'message' => 'Comment successfully created',
            'data' => $comment
        ];

        return response()->json($data, 201);
    }

    public function comment_list()
    {
        $comment = Comment::all();
        if ($comment) {
            $data = [
                'status' => true,
                'message' => "Here are all comments:",
                'data' => $comment
            ];
            return response()->json($data);
        }
    }

    public function comment_update(Request $request)
    {

        $user_id = $request->user()->id;
        $user = $request->user();
        if ($user) {
            $id = $request->id;
            $comment = Comment::find($id);
            $comment->user_id = $user_id;
            $comment->task_id = $request->task_id;
            $comment->comment_box = $request->comment_box;
            $comment->date = $request->date;
            $comment->save();

            $data = [
                "status" => true,
                "message" => 'The comment is successfull Updated',
                "data" => $comment
            ];
            return response()->json($data, 200);
        } else {
            $data = [
                "status" => false,
                "message" => 'The comment is not successfull Updated',
                "data" => []
            ];
            return response()->json($data, 404);
        }
    }

    public function comment_delete(Request $request, $id)
    {
        $comment = Comment::where('id', $id)->delete();
        if ($comment) {
            $data = [
                'status' => true,
                'message' => 'Comment is deleted',
                'data' => $comment
            ];
            return response()->json($data, 201);
        } else {
            $data = [
                'status' => false,
                'message' => 'Comment is not deleted',
                'data' => ['']
            ];
            return response()->json($data, 404);
        }
    }




    public function comments_for_specific_task(Request $request)
    {
        $task_id = $request->task_id;
        $comment = Task::find($task_id)->comment;
        $data = [
            'status' => true,
            'message' => 'Comment for specific task:',
            'data' => $comment
        ];
        return response()->json($data, 201);
    }

    public function my_Comment(Request $request)
    {
        $assign_id = $request->user()->id;
        $comment = TaskAssign::where('user_id', $assign_id)->with('task.comment')->get();
        // $comment=Comment::where('task_id',$comment)->get();
        $data = [
            'status' => true,
            'message' => "Your comment is:",
            'data' => $comment


        ];
        return response()->json($data, 201);
    }
}
