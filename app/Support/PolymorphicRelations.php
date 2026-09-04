<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class PolymorphicRelations
{

    public const ATTACHMENTABLE_PROJECT = 'project';
    public const ATTACHMENTABLE_TASK = 'task';
    public const ATTACHMENTABLE_MILESTONE = 'milestone';
    public const USER = 'user';


    public static function enforceMorphMap(): void
    {
        Relation::enforceMorphMap([
            ...self::attachmentables(),
            self::USER => User::class,
        ]);
    }

    /** @return array<string, class-string<Model>> */
    public static function attachmentables(): array
    {
        return [
            self::ATTACHMENTABLE_TASK => Task::class,
        ];
    }

    public static function normaliseAttachmentableType(?string $type): ?string
    {
        return self::normaliseType($type, self::attachmentables());
    }

    /** @return class-string<Model>|null */
    public static function classForAttachmentableAlias(string $alias): ?string
    {
        return self::attachmentables()[$alias] ?? null;
    }

    public static function findAttachmentable(string $alias, string $id): ?Model
    {
        $class = self::classForAttachmentableAlias($alias);

        return null === $class ? null : $class::query()->find($id);
    }

    /** @param array<string, class-string<Model>> $allowedTypes */
    private static function normaliseType(?string $type, array $allowedTypes): ?string
    {
        if (null === $type) {
            return null;
        }

        $normalised = mb_strtolower($type);

        foreach ($allowedTypes as $alias => $class) {
            if (
                $normalised === $alias
                || $normalised === $alias . 's'
                || $normalised === mb_strtolower($class)
                || $normalised === mb_strtolower(class_basename($class))
            ) {
                return $alias;
            }
        }

        return null;
    }
}
