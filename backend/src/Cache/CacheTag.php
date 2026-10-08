<?php

declare(strict_types=1);

namespace App\Cache;

/**
 * Tags of cached public API responses; PublicApiCacheInvalidator maps entities to them.
 */
final class CacheTag
{
    public const string PROJECTS = 'projects';
    public const string TECHNOLOGIES = 'technologies';
    public const string SKILLS = 'skills';
    public const string EXPERIENCE = 'experience';
}
