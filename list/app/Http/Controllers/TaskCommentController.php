<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaskComment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index()
    {
        $comments = TaskComment::with(['task', 'user'])->latest()->get();
        return view('admin.task_comments.index', compact('comments'));
    }

    public function create()
    {
        $tasks = Task::all();
        $users = User::all();
        return view('admin.task_comments.create', compact('tasks', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string',
        ]);

        TaskComment::create($request->all());

        return redirect()->route('admin.task_comments.index')->with('success', 'Komentar berhasil ditambahkan.');
    }

    public function edit(TaskComment $task_comment)
    {
        $tasks = Task::all();
        $users = User::all();
        return view('admin.task_comments.edit', compact('task_comment', 'tasks', 'users'));
    }

    public function update(Request $request, TaskComment $task_comment)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string',
        ]);

        $task_comment->update($request->all());

        return redirect()->route('admin.task_comments.index')->with('success', 'Komentar berhasil diperbarui.');
    }

    public function destroy(TaskComment $task_comment)
    {
        $task_comment->delete();
        return redirect()->route('admin.task_comments.index')->with('success', 'Komentar berhasil dihapus.');
    }
}
