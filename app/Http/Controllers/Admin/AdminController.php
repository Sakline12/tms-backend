<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class AdminController extends Controller
{
    public function user_list(Request $request)
    {
        // $id = $request->user()->id;
        // $user = User::find($id);

        // if (!$user || !$user->isActive) {

        //     $data = [
        //         'status' => false,
        //         'message' => 'Your are inactive',
        //         'data' => [],
        //     ];


        //     return response()->json($data, 404);
        // }
        $all_users = User::with('department')->with('designation')->get();

        $data = [
            'status' => true,
            'message' => 'Users are:',
            'data' => $all_users
        ];
        return response()->json($data, 201);
    }

    public function user_delete(Request $request, $id)
    {


        $user = User::findOrFail($id);

        $imageName = $user->image;

        if ($imageName) {
            $imagePath = public_path('profile/' . $imageName);

            if (file_exists($imagePath)) {
                // Delete the file
                unlink($imagePath);
            }
        }

        $user->delete();

        $data = [
            'status' => true,
            'message' => 'User is deleted',
            'data' => []
        ];

        return response()->json($data);
    }

    public function profile(Request $request)
    {
        $id = $request->user();
        $user = User::find($id);
        $data = [
            'status' => true,
            'message' => 'Comment for specific task:',
            'data' => $user
        ];
        return response()->json($data, 201);
    }
}
