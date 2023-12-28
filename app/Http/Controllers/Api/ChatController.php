<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Models\Blacklist;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{

    use MediaUploadingTrait;

    public function getRecentChat(Request $request)
    {
        try {
            $currentUserID = $request->input('currentUserID');

            $allMessages = Message::with(['user'])
                ->where(function ($query) use ($currentUserID) {
                    $query->where('sender_id', $currentUserID)
                        ->orWhere('receiver_id', $currentUserID);
                })
                ->orderBy('created_at', 'DESC')
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

            return response()->json($recentMessages);
        } catch (\Exception $e) {
            \Log::error('Error fetching recent messages: ' . $e->getMessage());
            return response()->json(['errorMessage' => 'Failed to fetch recent messages', 'error' => $e->getMessage()], 500);

        }
    }

    public function getMessages(Request $request)
    {
        try {
            $receiverId = $request->input('receiverId');
            $currentUserID = $request->input('currentUserID');

            $messages = empty($receiverId) ? [] : Message::whereIn('sender_id', [$currentUserID, $receiverId])
                ->whereIn('receiver_id', [$currentUserID, $receiverId])
                ->orderBy('created_at', 'ASC')
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

            return response()->json($messages);

        } catch (\Exception $e) {
            \Log::error('Error fetching messages content: ' . $e->getMessage());
            return response()->json(['errorMessage' => 'Failed to fetch messages content', 'error' => $e->getMessage()], 500);

        }
    }

    function sendMessage(Request $request)
    {
        try {
            $receiverId = $request->input('receiverID');
            $currentUserID = $request->input('currentUserID');
            $message = $request->input('message');
            $images = json_decode($request->input('images'));

            if ($receiverId === $currentUserID) {
                return response()->json(['errorMessage' => 'Receiver ID cannot be the same as the sender ID'], 400);
            }

            $messages = Message::create([
                'receiver_id' => $receiverId,
                'sender_id' => $currentUserID,
                'message' => $message,
            ]);

            if ($images) {
                foreach ($images as $image) {
                    // Add media conversion here
                    $messages->addMedia(storage_path('tmp/uploads/' . basename($image->name)))
                        ->toMediaCollection('chat_images');

                }
            }

            return response()->json(['message' => 'Message sent successfully'], 200);
        } catch (\Exception $e) {
            \Log::error('sendMessage error: ' . $e->getMessage());

            return response()->json(['errorMessage' => 'Failed to send message', 'error' => $e->getMessage()], 500);
        }
    }


    public function searchUsers(Request $request)
    {
        try {
            $query = $request->input('q');
            $users = User::where('name', 'LIKE', "%{$query}%")->get();

            return response()->json([
                'message' => 'Users fetched successfully',
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error in searchUsers: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function countUnreadMessages(Request $request)
    {
        $currentUserID = $request->input('currentUserID');
        $receiverID = $request->input('receiverID');
        $unreadCount = Message::where('receiver_id', $currentUserID)
            ->where('sender_id', $receiverID)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unreadCount' => $unreadCount]);
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


    function getBlackList(Request $request)
    {
        $currentUserID = $request->input('currentUserID');

        $blacklist = Blacklist::with('user')
            ->where('sender_id', $currentUserID)
            ->get();

        return response()->json($blacklist);
    }


    function addBlacklist(Request $request)
    {
        $receiverID = $request->input('receiverID');
        $currentUserID = $request->input('currentUserID');

        DB::beginTransaction();

        try {
            $blacklist = Blacklist::firstOrCreate(
                [
                    'receiver_id' => $receiverID,
                    'sender_id' => $currentUserID,
                ],
                [
                    'status' => 'block',
                ]
            );

            DB::commit();

            if ($blacklist->wasRecentlyCreated) {
                return response()->json(['message' => 'User blocked successfully'], 200);
            } else {
                return response()->json(['message' => 'User already blocked'], 200);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['errorMessage' => 'Failed to block user', 'error' => $e->getMessage()], 500);
        }
    }

    function deleteBlacklist(Request $request)
    {
        $receiverID = $request->input('receiverID');

        DB::beginTransaction();

        try {

            Blacklist::where('id', $receiverID)->delete();

            DB::commit();

            return response()->json(['message' => 'User unblock successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['errorMessage' => 'Failed to unblock user', 'error' => $e->getMessage()], 500);
        }
    }

    function checkBlackList(Request $request)
    {

        $receiverId = $request->input('receiverId');
        $currentUserID = $request->input('currentUserID');
        Log::info($receiverId);
        Log::info($currentUserID);
        $isBlock = Blacklist::where(function ($query) use ($currentUserID, $receiverId) {
            $query->where('sender_id', $currentUserID)
                ->where('receiver_id', $receiverId);
        })
            ->orWhere(function ($query) use ($currentUserID, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $currentUserID);
            })
            ->where('status', 'block')
            ->first();

        if ($isBlock) {
            if ($isBlock->sender_id == $currentUserID) {
                return response()->json(['message' => 'You have blocked this user'], 200);
            } else {
                return response()->json(['message' => 'You have been blocked by the user'], 200);
            }
        }

        return $isBlock;
    }


    function deleteChat(Request $request)
    {
        $receiverID = $request->input('receiverID');
        $currentUserID = $request->input('currentUserID');

        DB::beginTransaction();

        try {

            Message::where(function ($query) use ($receiverID, $currentUserID) {
                $query->where('sender_id', $currentUserID)
                    ->where('receiver_id', $receiverID);
            })->orWhere(function ($query) use ($receiverID, $currentUserID) {
                $query->where('sender_id', $receiverID)
                    ->where('receiver_id', $currentUserID);
            })->delete();

            DB::commit();

            return response()->json(['message' => 'Chat history deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['errorMessage' => 'Failed to delete chat history', 'error' => $e->getMessage()], 500);
        }
    }


    function countResponseRate()
    {
        try {
            $allMessages = Message::orderBy('created_at', 'ASC')->get();
            Log::info($allMessages);
            // Group messages by receiver_id and then by sender_id
            $groupedMessages = [];

            foreach ($allMessages as $message) {
                $senderId = $message->sender_id;
                $receiverId = $message->receiver_id;

                // Skip if senderId and receiverId are the same
                if ($senderId === $receiverId) {
                    continue;
                }

                // Groups for sender
                $senderPairKey = $senderId . '-' . $receiverId;
                if (!isset($groupedMessages[$senderId])) {
                    $groupedMessages[$senderId] = [];
                }
                if (!isset($groupedMessages[$senderId][$senderPairKey])) {
                    $groupedMessages[$senderId][$senderPairKey] = [];
                }
                $groupedMessages[$senderId][$senderPairKey][] = $message;

                // Groups for receiver (if different from sender)
                if ($senderId !== $receiverId) {
                    $receiverPairKey = $receiverId . '-' . $senderId;
                    if (!isset($groupedMessages[$receiverId])) {
                        $groupedMessages[$receiverId] = [];
                    }
                    if (!isset($groupedMessages[$receiverId][$receiverPairKey])) {
                        $groupedMessages[$receiverId][$receiverPairKey] = [];
                    }
                    $groupedMessages[$receiverId][$receiverPairKey][] = $message;
                }
            }

            $replyRates = [];
            foreach ($groupedMessages as $senderId => $conversations) {
                Log::info('sid', [$senderId]);
                $conversationCounts = [
                    'receiveMessage' => [],
                    'senderReply' => []
                ];
                $delayReplies = [];

                foreach ($conversations as $conversationKey => $conversation) {
                    $lastMessageFromOthers = null;
                    $waitingForReply = true;
                    $conversationCounts['receiveMessage'][$conversationKey] = 0;
                    $conversationCounts['senderReply'][$conversationKey] = 0;
                    $delayReplies[$conversationKey] = 0;

                    foreach ($conversation as $message) {
                        //Log::info('out',[$message]);

                        if ($waitingForReply && $message['sender_id'] != $senderId) { // Message from the other party

                            $lastMessageFromOthers = $message;
                            //Log::info('in',[$lastMessageFromOthers]);
                            $conversationCounts['receiveMessage'][$conversationKey]++;
                            $waitingForReply = false;

                        } elseif (!$waitingForReply && $message['sender_id'] == $senderId) {
                            $intervalInSeconds = strtotime($message['created_at']) - strtotime($lastMessageFromOthers['created_at']);
                            $intervalInHours = $intervalInSeconds / 3600;
                            $conversationCounts['senderReply'][$conversationKey]++;

                            // Log the details
                            Log::info("Replying to Message ID: {$lastMessageFromOthers['id']}, Message: '{$lastMessageFromOthers['message']}', Created At: {$lastMessageFromOthers['created_at']}; Reply Message ID: {$message['id']}, Reply: '{$message['message']}', Reply Created At: {$message['created_at']}, Interval Time: {$intervalInHours} hours");

                            // Calculate delay penalty for this reply
                            if ($intervalInHours > 6 && $intervalInHours <= 12) {
                                $delayReplies[$conversationKey] += 0.5;
                            } elseif ($intervalInHours > 12) {
                                $delayReplies[$conversationKey] += 1;
                            }

                            $waitingForReply = true;
                            $lastMessageFromOthers = null; // Reset for the next pair
                        }
                    }
                }

                $totalReceiveMessage = array_sum($conversationCounts['receiveMessage']);
                $totalSenderReply = array_sum($conversationCounts['senderReply']);
                $totalDelayReply = array_sum($delayReplies);

                Log::info('Sender ID: ' . $senderId . ' - Total Messages Received: ' . $totalReceiveMessage . ', Total Replies Sent: ' . $totalSenderReply . ', Total Delay Penalty: ' . $totalDelayReply);

                if ($totalReceiveMessage > 0) {
                    $averageReplyRate = (($totalSenderReply / $totalReceiveMessage) * 100) - $totalDelayReply;
                } else {
                    $averageReplyRate = 100;
                }

                $averageReplyRate = round(max(0, min(100, $averageReplyRate)), 2);
                $replyRates[$senderId] = $averageReplyRate;
                Log::info('Sender ID: ' . $senderId . ' - Average Reply Rate: ' . $averageReplyRate);
            }

            return response()->json([
                'group' => $groupedMessages,
                'replyRates' => $replyRates
            ]);

        } catch (\Exception $e) {
            \Log::error('Error count response rate: ' . $e->getMessage());
            return response()->json(['errorMessage' => 'Failed to count response rate'], 500);
        }
    }




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
}
