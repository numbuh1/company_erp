<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|in:project,task',
            'commentable_id'   => 'required|integer',
            'body'             => 'required|string|max:5000',
            'images.*'         => 'nullable|image|max:5120',
        ]);

        $modelClass = $request->commentable_type === 'project'
            ? Project::class
            : Task::class;

        $model = $modelClass::findOrFail($request->commentable_id);

        $filenames = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = 'comment_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('comment_images', $filename, 'public');
                $filenames[] = $filename;
            }
        }

        Comment::create([
            'user_id'          => auth()->id(),
            'commentable_type' => $modelClass,
            'commentable_id'   => $model->id,
            'body'             => $request->body,
            'images'           => $filenames ?: null,
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== auth()->id() && !auth()->user()->can('edit all user')) {
            abort(403);
        }

        if ($comment->images) {
            foreach ($comment->images as $img) {
                Storage::disk('public')->delete('comment_images/' . $img);
            }
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}