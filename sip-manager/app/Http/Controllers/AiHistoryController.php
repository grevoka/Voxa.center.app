<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use Illuminate\Http\Request;

class AiHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = AiConversation::orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('caller_number', 'like', "%{$s}%")
                  ->orWhere('transcript', 'like', "%{$s}%");
            });
        }

        if ($request->filled('model')) {
            $query->where('model', 'like', '%' . $request->input('model') . '%');
        }

        $conversations = $query->paginate(25)->withQueryString();

        $totals = [
            'count' => AiConversation::count(),
            'duration' => AiConversation::sum('duration_sec'),
            'cost' => AiConversation::sum('cost_estimated'),
            'turns' => AiConversation::sum('turns'),
        ];

        return view('ai-history.index', compact('conversations', 'totals'));
    }

    public function show(AiConversation $conversation)
    {
        return response()->json([
            'id' => $conversation->id,
            'caller' => $conversation->caller_number,
            'called' => $conversation->called_number,
            'model' => $conversation->model,
            'voice' => $conversation->voice,
            'duration' => $conversation->duration_sec,
            'turns' => $conversation->turns,
            'cost' => $conversation->cost_estimated,
            'hangup' => $conversation->hangup_reason,
            'date' => $conversation->created_at->format('d/m/Y H:i:s'),
            'transcript' => $conversation->transcript ?? [],
        ]);
    }
}
