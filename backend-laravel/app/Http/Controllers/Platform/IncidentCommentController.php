<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncidentCommentController extends Controller
{
    public function store(Request $request, Incident $incident): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $incident->comments()->create([
            'user_id'   => auth()->id(),
            'user_name' => auth()->user()->name,
            'body'      => $validated['body'],
            'is_system' => false,
        ]);

        return back()->with('comment_success', true)->withFragment('comments');
    }

    public function destroy(Incident $incident, IncidentComment $comment): RedirectResponse
    {
        // Seuls l'auteur ou un admin peuvent supprimer
        abort_unless(
            $comment->user_id === auth()->id() || auth()->user()->isAdmin(),
            403
        );

        abort_unless($comment->incident_id === $incident->id, 404);

        $comment->delete();

        return back()->with('comment_deleted', true)->withFragment('comments');
    }
}
