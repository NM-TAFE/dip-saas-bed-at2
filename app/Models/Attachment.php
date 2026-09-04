<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasMongoUlidKey;
use Carbon\CarbonInterface;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $id
 * @property string $file_path
 * @property string $attachmentable_id
 * @property string $attachmentable_type
 * @property string $uploaded_by
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property User|null $uploadedBy
 */
final class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    use HasMongoUlidKey;
    use SoftDeletes;

    protected $connection = 'mongodb';

    protected $table = 'attachments';

    /** @var list<string> */
    protected $fillable = [
        'attachmentable_id',
        'attachmentable_type',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    /** @var list<string> */
    protected $hidden = ['_id'];

    /** @var list<string> */
    protected $appends = ['id', 'file_path'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function attachmentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', '_id');
    }

    // Storage accessor
    public function getFilePathAttribute(): string
    {
        return (string) ($this->attributes['path'] ?? '');
    }
}
