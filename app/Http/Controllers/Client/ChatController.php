<?php

namespace App\Http\Controllers\Client;


use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{

    /*******************************Format Date Function*****************************************/

    function formatTimestamp($timestamp)
    {
        // Convert the timestamp to the local timezone
        $messageTime = Carbon::parse($timestamp)->setTimezone(config('app.timezone'));

        $now = Carbon::now();
        $timeDiff = $now->diffInMinutes($messageTime);

        if ($timeDiff < 60) {
            // Within 1 hour, display minutes ago
            return $timeDiff . ' minutes ago';
        } elseif ($timeDiff < 1440) {
            // Within 24 hours, display hours and minutes in 12-hour format
            return $messageTime->format('h:i A');
        } elseif ($timeDiff < 2880) {
            // Within 48 hours, display yesterday
            return 'Yesterday';
        } else {
            // More than 48 hours, display the date
            return $messageTime->format('Y-m-d');
        }
    }
    /*******************************Format Date Function*****************************************/



    /*******************************Local Function*****************************************/

    function viewChat(Request $request, ?string $receiverId = null)
    {
        $firebase_uid = $request->user()->firebase_uid;

        $messages = empty($receiverId) ? [] : Message::whereIn('sender_id', [$firebase_uid, $receiverId])
            ->whereIn('receiver_id', [$firebase_uid, $receiverId])
            ->orderBy('created_at','ASC')
            ->get();

        $previousMessage = null;
        foreach ($messages as $message) {
            $message->read_at = $this->formatTimestamp($message->read_at);

            if ($previousMessage) {
                $hoursDiff = $message->created_at->diffInHours($previousMessage->created_at);
                if ($hoursDiff > 1 && $hoursDiff <= 24) {
                    // If more than 1 hour but less than or equal to 24 hours
                    $message->custom_date = $message->created_at->format('h:i A');
                } elseif ($hoursDiff > 24 && $hoursDiff <= 48) {
                    // If more than 24 hours but less than or equal to 36 hours
                    $message->custom_date = 'Yesterday';
                } elseif ($hoursDiff > 48) {
                    // If more than 36 hours
                    $message->custom_date = $message->created_at->format('Y-m-d');
                } else {
                    // If 1 hour or less
                    $message->custom_date = null;
                }
            } else {
                // For the first message, compare with the current time
                $hoursDiff = $message->created_at->diffInHours(Carbon::now());
                if ($hoursDiff <= 24) {
                    $message->custom_date = $message->created_at->format('h:i A');
                } elseif ($hoursDiff <= 48) {
                    $message->custom_date = 'Yesterday';
                } else {
                    $message->custom_date = $message->created_at->format('Y-m-d');
                }
            }

            $message->custom_time = $message->created_at->format('h:i A');
            $previousMessage = $message;
        }

        if (!empty($receiverId)) {
            Message::where('sender_id', $receiverId)
                ->where('receiver_id', $firebase_uid)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }


        $currentUserID = $request->user()->firebase_uid;
        $allMessages = Message::with(['user'])
            ->where(function ($query) use ($currentUserID) {
                $query->where('sender_id', $currentUserID)
                    ->orWhere('receiver_id', $currentUserID);
            })
            ->orderBy('created_at','DESC')
            ->get();

        $recentMessages = [];
        $usedUserIds = [];
        foreach ($allMessages as $message) {
            $userId = $message->sender_id == $currentUserID ? $message->receiver_id : $message->sender_id;

//            $latestFriendTimestamp = $message->created_at;

            if (!in_array($userId, $usedUserIds) && $userId != $currentUserID) {


//                $latestMessageTimestamp = optional($latestMessage)->created_at;
//                $latestTimestamp = $latestFriendTimestamp > $latestMessageTimestamp ? $latestFriendTimestamp : $latestMessageTimestamp;

                $recentMessages[] = [
                    'user_id' => $userId,
                    'message' => $message->message,
                    'friend_created_at' => $message->created_at,
                    'message_created_at' => optional($message)->created_at,
                    'formatted_timestamp' => $this->formatTimestamp(optional($message)->created_at ?? $message->created_at),
                ];
                $usedUserIds[] = $userId;
            }

            // Sort the recentMessages array
            usort($recentMessages, function ($a, $b) {
                $aTimestamp = $a['message_created_at'] ?? $a['friend_created_at'];
                $bTimestamp = $b['message_created_at'] ?? $b['friend_created_at'];

                // Sort by latest activity, whether it's a message or a friend connection
                return $bTimestamp <=> $aTimestamp;
            });

        }

        foreach ($recentMessages as $key => $userMessage) {
            $user = User::where('firebase_uid', $userMessage['user_id'])->first(['name', 'firebase_uid']);
            $recentMessages[$key]['name'] = $user->name ?? '';
            $recentMessages[$key]['firebase_uid'] = $user->firebase_uid ?? '';
        }

        $receiver = User::where('firebase_uid',$receiverId)->get();

        return inertia('Chat/Chat', compact('recentMessages', 'receiver', 'messages', 'currentUserID'));

    }



    public function sendMessage(Request $request, ?string $receiverId = null)
    {

        $messageContent = $request->input('message');
        $currentUserID = $request->user()->firebase_uid;

        $response = Message::create([
            'receiver_id' => $receiverId,
            'sender_id' => $currentUserID,
            'message' => $messageContent,
        ]);

        return to_route('client.viewChat', $receiverId);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->input('q');
        $users = User::where('name', 'LIKE', "%{$query}%")
            ->get();

        return response()->json(['users' => $users]);
    }

    public function addFriend(Request $request)
    {
        $userId = $request->user()->id;
        $friendId = $request->input('friendId');

        $currentUserID = $request->input('currentUserID');
        $friendIds = $request->input('friendIds');

        $existingFriendRequest = Blacklist::where(function ($query) use ($userId, $friendId) {
            $query->where('receiver_id', $userId)->where('sender_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('receiver_id', $friendId)->where('sender_id', $userId);
        })->first();

        $existingFriendRequests = Blacklist::where(function ($query) use ($currentUserID, $friendIds) {
            $query->where('receiver_id', $currentUserID)->where('sender_id', $friendIds);
        })->orWhere(function ($query) use ($currentUserID, $friendIds) {
            $query->where('receiver_id', $friendIds)->where('sender_id', $currentUserID);
        })->first();

        if ($existingFriendRequest || $existingFriendRequests) {
            return response()->json(['exists' => 'Friend request already exists.'], 422);
        }

        DB::beginTransaction();

        try {
            Blacklist::create([
                'receiver_id' => $friendId ?? $friendIds,
                'sender_id' => $userId ?? $currentUserID,
                'status' => "pending"
            ]);

            DB::commit();

            return response()->json(['message' => 'Friend request sent']);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['message' => 'Failed to add friend', 'error' => $e->getMessage()], 500);
        }
    }

    function countUnreadMessages(Request $request)
    {
        $currentUserID = $request->input('currentUserID');
        $receiverID = $request->input('receiverID');
        $unreadCount = Message::where('receiver_id', $currentUserID)
            ->where('sender_id', $receiverID)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unreadCount' => $unreadCount]);
    }

    public function localGetMessages(Request $request)
    {
        try {
            $receiverId = $request->input('receiverId');
            $currentUserID = $request->input('currentUserID');

            $messages = empty($receiverId) ? [] : Message::whereIn('sender_id', [$currentUserID, $receiverId])
                ->whereIn('receiver_id', [$currentUserID, $receiverId])
                ->orderBy('created_at','ASC')
                ->get();

            $previousMessage = null;
            foreach ($messages as $message) {
                $message->read_at = $this->formatTimestamp($message->read_at);

                if ($previousMessage) {
                    $hoursDiff = $message->created_at->diffInHours($previousMessage->created_at);
                    if ($hoursDiff > 1 && $hoursDiff <= 24) {
                        // If more than 1 hour but less than or equal to 24 hours
                        $message->custom_date = $message->created_at->format('h:i A');
                    } elseif ($hoursDiff > 24 && $hoursDiff <= 48) {
                        // If more than 24 hours but less than or equal to 36 hours
                        $message->custom_date = 'Yesterday';
                    } elseif ($hoursDiff > 48) {
                        // If more than 36 hours
                        $message->custom_date = $message->created_at->format('Y-m-d');
                    } else {
                        // If 1 hour or less
                        $message->custom_date = null;
                    }
                } else {
                    // For the first message, compare with the current time
                    $hoursDiff = $message->created_at->diffInHours(Carbon::now());
                    if ($hoursDiff <= 24) {
                        $message->custom_date = $message->created_at->format('h:i A');
                    } elseif ($hoursDiff <= 48) {
                        $message->custom_date = 'Yesterday';
                    } else {
                        $message->custom_date = $message->created_at->format('Y-m-d');
                    }
                }

                $message->custom_time = $message->created_at->format('h:i A');
                $previousMessage = $message;
            }
//            Log::info(response()->json($messages));
            return response()->json($messages);

        } catch (\Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch messages'], 500);
        }
    }

    public function markMessagesAsRead(Request $request)
    {
        $currentUserID = $request->input('currentUserID');
        $receiverId = $request->input('receiverId');

        $read = Message::where('receiver_id', $currentUserID)
            ->where('sender_id', $receiverId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($read) {
            return response()->json(['message' => 'Read'], 200);
        } else {
            return response()->json(['errorMessage' => 'Message not found or no message to read'], 404);
        }
    }


    public function getRecentChat(Request $request)
    {
        try {
            $currentUserID = $request->input('currentUserID');

            $allMessages = Message::with(['user'])
                ->where(function ($query) use ($currentUserID) {
                    $query->where('sender_id', $currentUserID)
                        ->orWhere('receiver_id', $currentUserID);
                })
                ->orderBy('created_at','DESC')
                ->get()
                ->reject(function ($message) {
                    return $message->isBlocked();
                });

            $recentMessages = [];
            $usedUserIds = [];
            foreach ($allMessages as $message) {
                $userId = $message->sender_id == $currentUserID ? $message->receiver_id : $message->sender_id;

                if (!in_array($userId, $usedUserIds) && $userId != $currentUserID) {
                    $latestMessage = Message::where(function ($query) use ($currentUserID, $userId) {
                        $query->where('sender_id', $currentUserID)->where('receiver_id', $userId);
                        $query->orWhere(function ($query) use ($currentUserID, $userId) {
                            $query->where('sender_id', $userId)->where('receiver_id', $currentUserID);
                        });
                    })->orderBy('created_at', 'DESC')->first();

                    $recentMessages[] = [
                        'user_id' => $userId,
                        'message' => $latestMessage ? $latestMessage->message : null,
                        'read_at' => $message->read_at,
                        'friend_created_at' => $message->created_at,
                        'message_created_at' => optional($latestMessage)->created_at,
                        'formatted_timestamp' => $this->formatTimestamp(optional($latestMessage)->created_at ?? $message->created_at),
                    ];
                    $usedUserIds[] = $userId;
                }

                // Sort the recentMessages array
                usort($recentMessages, function ($a, $b) {
                    $aTimestamp = $a['message_created_at'] ?? $a['friend_created_at'];
                    $bTimestamp = $b['message_created_at'] ?? $b['friend_created_at'];

                    // Sort by latest activity, whether it's a message or a friend connection
                    return $bTimestamp <=> $aTimestamp;
                });
            }

            foreach ($recentMessages as $key => $userMessage) {
                $user = User::where('firebase_uid', $userMessage['user_id'])->first(['name', 'firebase_uid']);
                $recentMessages[$key]['name'] = $user->name ?? '';
                $recentMessages[$key]['firebase_uid'] = $user->firebase_uid ?? '';
            }


//            \Log::debug('Fetched Friends Accept successfully.', ['count' => count([$recentMessages])]);

            return response()->json($recentMessages);
        } catch (\Exception $e) {
            \Log::error('Error fetching messages: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch messages'], 500);
        }
    }


    /*******************************Local Function*****************************************/


}
