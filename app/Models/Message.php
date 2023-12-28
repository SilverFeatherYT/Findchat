<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class Message extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

//    protected $connection = 'findchat';
    public $table = 'messages';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'read_at'
    ];

    protected $dates = [
        'read_at',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'chat_images',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id','firebase_uid');
    }

    public function isBlocked()
    {
        $isBlocked = Blacklist::where(function ($query) {
            $query->where('sender_id', $this->sender_id)
                ->where('receiver_id', $this->receiver_id);
        })->orWhere(function ($query) {
            $query->where('sender_id', $this->receiver_id)
                ->where('receiver_id', $this->sender_id);
        })->where('status', 'block')->exists();

        return $isBlocked;
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    function formatTimestamp($timestamp)
    {
        // Convert the timestamp to the local timezone
        $messageTime = Carbon::parse($timestamp)->setTimezone(config('app.timezone'));

        $now = Carbon::now();
        $timeDiff = $now->diffInMinutes($messageTime);

        if ($timeDiff < 60) {
            // Within 1 hour, display minutes ago
            return $timeDiff . ' minutes ago';
        } else {
            // Within 24 hours, display hours and minutes in 12-hour format
            return $messageTime->format('h:i A');
        }
    }

    public function getChatImagesAttribute()
    {
        $files = $this->getMedia('chat_images');
        $files->each(function ($item) {
            $item->url = $item->getUrl();
            $item->preview = $item->getUrl('preview');
            $item->height = Image::make($item->getPath())->height();
            $item->width = Image::make($item->getPath())->width();
        });

        return $files;
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('preview')->height(720)->optimize();
    }


}
