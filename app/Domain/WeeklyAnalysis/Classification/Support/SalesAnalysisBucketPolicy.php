<?php

namespace App\Domain\WeeklyAnalysis\Classification\Support;

class SalesAnalysisBucketPolicy
{
  private const EXCLUSIVE_BUCKETS = ['rhp', 'parts_tsd'];

    /**
     * @return array<int, string>
     */
    public static function exclusiveBuckets(): array
    {
        return self::EXCLUSIVE_BUCKETS;
    }

    public static function mappingRulesConflict(?string $existingBucket, ?string $newBucket): bool
    {
        $existingBucket = self::normalizeBucket($existingBucket);
        $newBucket = self::normalizeBucket($newBucket);

        if ($existingBucket === $newBucket) {
            return true;
        }

        $exclusive = self::exclusiveBuckets();

        return in_array($existingBucket, $exclusive, true)
            && in_array($newBucket, $exclusive, true);
    }

    public static function normalizeBucket(?string $bucket): string
    {
        return $bucket ?: 'raw';
    }
}
