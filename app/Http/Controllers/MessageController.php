<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Afficher la liste des messages reçus.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->inbox();
    }

    /**
     * Afficher la boîte de réception de l'utilisateur.
     *
     * @return \Illuminate\Http\Response
     */
    public function inbox()
    {
        $user = Auth::user();

        // Récupérer les messages directs
        $directMessages = Message::where('recipient_id', $user->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        // Récupérer les messages de groupe selon le rôle de l'utilisateur
        $groupMessages = collect();

        if ($user->role === 'student') {
            $student = Student::where('user_id', $user->id)->first();
            if ($student) {
                $groupMessages = Message::where(function($query) use ($student) {
                    $query->where('recipient_type', 'class')
                          ->where('recipient_group', $student->class_id);
                })->orWhere(function($query) {
                    $query->where('recipient_type', 'students')
                          ->whereNull('recipient_group');
                })->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                          ->whereNull('recipient_group');
                })->whereNull('parent_id')
                  ->orderBy('created_at', 'desc')
                  ->get();
            }
        } elseif ($user->role === 'teacher') {
            $teacher = Teacher::where('user_id', $user->id)->first();
            if ($teacher) {
                $groupMessages = Message::where(function($query) use ($teacher) {
                    $query->where('recipient_type', 'department')
                          ->where('recipient_group', $teacher->department_id);
                })->orWhere(function($query) {
                    $query->where('recipient_type', 'teachers')
                          ->whereNull('recipient_group');
                })->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                          ->whereNull('recipient_group');
                })->whereNull('parent_id')
                  ->orderBy('created_at', 'desc')
                  ->get();
            }
        } elseif ($user->role === 'admin' || $user->role === 'superadmin') {
            $groupMessages = Message::where(function($query) {
                $query->where('recipient_type', 'admins')
                      ->whereNull('recipient_group');
            })->orWhere(function($query) {
                $query->where('recipient_type', 'all')
                      ->whereNull('recipient_group');
            })->whereNull('parent_id')
              ->orderBy('created_at', 'desc')
              ->get();
        }

        // Fusionner les messages directs et de groupe
        $messages = $directMessages->merge($groupMessages)->sortByDesc('created_at');

        return view('messages.inbox', compact('messages'));
    }

    /**
     * Afficher les messages envoyés par l'utilisateur.
     *
     * @return \Illuminate\Http\Response
     */
    public function sent()
    {
        $messages = Message::where('sender_id', Auth::id())
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('messages.sent', compact('messages'));
    }

    /**
     * Afficher le formulaire de création d'un nouveau message.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('messages.create', compact('users'));
    }

    /**
     * Enregistrer un nouveau message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $request->recipient_id,
            'subject' => $request->subject,
            'content' => $request->content,
            'recipient_type' => 'user',
        ]);

        return redirect()->route('messages.show', $message)
            ->with('success', 'Message envoyé avec succès.');
    }

    /**
     * Envoyer un message à un groupe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendToGroup(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|string|in:all_students,specific_classes,specific_students',
            'selected_classes' => 'required_if:recipient_type,specific_classes|array',
            'selected_students' => 'required_if:recipient_type,specific_students|array',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Traitement selon le type de destinataire
        if ($request->recipient_type === 'all_students') {
            // Envoyer à tous les étudiants
            $message = Message::create([
                'sender_id' => Auth::id(),
                'subject' => $request->subject,
                'content' => $request->content,
                'recipient_type' => 'students',
                'recipient_group' => null,
            ]);
        } elseif ($request->recipient_type === 'specific_classes') {
            // Envoyer à des classes spécifiques
            foreach ($request->selected_classes as $classId) {
                Message::create([
                    'sender_id' => Auth::id(),
                    'subject' => $request->subject,
                    'content' => $request->content,
                    'recipient_type' => 'class',
                    'recipient_group' => $classId,
                ]);
            }

            // Rediriger vers la boîte d'envoi après avoir envoyé à plusieurs classes
            return redirect()->route('messages.sent')
                ->with('success', 'Messages envoyés avec succès aux classes sélectionnées.');
        } elseif ($request->recipient_type === 'specific_students') {
            // Envoyer à des étudiants spécifiques
            foreach ($request->selected_students as $studentId) {
                $student = Student::find($studentId);
                if ($student && $student->user) {
                    Message::create([
                        'sender_id' => Auth::id(),
                        'recipient_id' => $student->user->id,
                        'subject' => $request->subject,
                        'content' => $request->content,
                        'recipient_type' => 'user',
                    ]);
                }
            }

            // Rediriger vers la boîte d'envoi après avoir envoyé à plusieurs étudiants
            return redirect()->route('messages.sent')
                ->with('success', 'Messages envoyés avec succès aux étudiants sélectionnés.');
        }

        return redirect()->route('messages.sent')
            ->with('success', 'Message de groupe envoyé avec succès.');
    }

    /**
     * Afficher un message spécifique.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function show(Message $message)
    {
        // Vérifier si l'utilisateur a le droit de voir ce message
        $user = Auth::user();

        if ($message->recipient_id === $user->id || $message->sender_id === $user->id) {
            // Message direct
            if ($message->recipient_id === $user->id && !$message->is_read) {
                $message->markAsRead();
            }
        } elseif ($message->isGroupMessage()) {
            // Message de groupe - vérifier si l'utilisateur fait partie du groupe
            $canView = false;

            if ($message->recipient_type === 'all') {
                $canView = true;
            } elseif ($message->recipient_type === 'students' && $user->role === 'student') {
                $canView = true;
            } elseif ($message->recipient_type === 'teachers' && $user->role === 'teacher') {
                $canView = true;
            } elseif ($message->recipient_type === 'admins' && ($user->role === 'admin' || $user->role === 'superadmin')) {
                $canView = true;
            } elseif ($message->recipient_type === 'class' && $user->role === 'student') {
                $student = Student::where('user_id', $user->id)->first();
                $canView = $student && $student->class_id == $message->recipient_group;
            } elseif ($message->recipient_type === 'department' && $user->role === 'teacher') {
                $teacher = Teacher::where('user_id', $user->id)->first();
                $canView = $teacher && $teacher->department_id == $message->recipient_group;
            }

            if (!$canView) {
                return redirect()->route('messages.index')
                    ->with('error', 'Vous n\'avez pas accès à ce message.');
            }
        } else {
            return redirect()->route('messages.index')
                ->with('error', 'Vous n\'avez pas accès à ce message.');
        }

        // Récupérer les réponses au message
        $replies = $message->replies()->orderBy('created_at', 'asc')->get();

        return view('messages.show', compact('message', 'replies'));
    }

    /**
     * Répondre à un message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request, Message $message)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $reply = Message::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $message->sender_id,
            'subject' => 'Re: ' . $message->subject,
            'content' => $request->content,
            'recipient_type' => 'user',
            'parent_id' => $message->id,
        ]);

        return redirect()->route('messages.show', $message)
            ->with('success', 'Réponse envoyée avec succès.');
    }

    /**
     * Marquer un message comme lu.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function markAsRead(Message $message)
    {
        if ($message->recipient_id === Auth::id()) {
            $message->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Supprimer un message.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function destroy(Message $message)
    {
        // Vérifier si l'utilisateur a le droit de supprimer ce message
        if ($message->sender_id === Auth::id() || $message->recipient_id === Auth::id()) {
            $message->delete();
            return redirect()->route('messages.index')
                ->with('success', 'Message supprimé avec succès.');
        }

        return redirect()->route('messages.index')
            ->with('error', 'Vous n\'avez pas le droit de supprimer ce message.');
    }
}
