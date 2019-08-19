<?php

namespace App\Http\Controllers;

use App\Chat;
use App\Message;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MessageController extends Controller
{

    private function updateChat($from, $to, $last, $unread) {
        $chat = Chat::where('from_user_id', $from)
            ->where('to_user_id', $to)
            ->first();
        if (!$chat) {
            $chat = Chat::create([
                'from_user_id' => $from,
                'to_user_id' => $to,
                'unread' => $unread,
                'last' => $last,
                'last_message_time' => Carbon::now()
            ]);
        } else {
            $chat->unread += $unread;
            $chat->last = $last;
            $chat->last_message_time = Carbon::now();
            $chat->save();
        }
        return $chat;
    }

    private function clearUnread($from, $to) {
        Chat::where('from_user_id', $from)
            ->where('to_user_id', $to)
            ->update(["unread" => 0]);
    }


    public function send(Request $request) {
        $to = intval($request->input('to', -1));
        $type = 0;
        $content = $request->input('content', '');
        if ($to < 0 || $content == '') {
            return response()->json([
                'success' => false,
                'data' => '400'
            ]);
        }
        if (!User::find($to)) {
            return response()->json([
                'success' => false,
                'data' => 'user not found'
            ]);
        }
        $message = Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $to,
            'type' => $type,
            'content' => $content
        ]);

        $this->updateChat(Auth::id(), $to, $content, 0);
        $this->updateChat($to, Auth::id(), $content, 1);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }


    public function getChatList(Request $request) {
        $limit = intval($request->input('limit', 20));
        $offset = intval($request->input('offset', 0));

        $chats = Chat::where('from_user_id', Auth::id())
            ->with(['to_user'])
            ->orderBy('last_message_time', 'desc')
            ->skip($offset)->take($limit)->get();
        return response()->json(['success' => true, 'data' => $chats]);
    }

    public function getMessageList(Request $request) {
        $limit = intval($request->input('limit', 20));
        $offset = intval($request->input('offset', 0));
        $to = intval($request->input('to', -1));
        if ($to < 0) {
            return response()->json([
                'success' => false,
                'data' => '400'
            ]);
        }
        if (!User::find($to)) {
            return response()->json([
                'success' => false,
                'data' => 'user not found'
            ]);
        }
        $messages = Message::where(function($query) use ($to) {
            $query->where(function($query) use ($to) {
                $query->where('from_user_id', Auth::id());
                $query->where('to_user_id', $to);
            });
            $query->orWhere(function($query) use ($to) {
                $query->where('from_user_id', $to);
                $query->where('to_user_id', Auth::id());
            });
        })
            ->orderBy('id', 'desc')
            ->skip($offset)->take($limit)->get();
        $this->clearUnread(Auth::id(), $to);
        return response()->json(['success' => true, 'data' => $messages]);
    }

}
