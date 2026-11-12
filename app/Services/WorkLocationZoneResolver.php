<?php

namespace App\Services;

class WorkLocationZoneResolver
{
    /**
     * Resolve a work location only when its configured keywords point to one
     * unambiguous zone. Unknown or overlapping locations remain unassigned.
     */
    public function resolve(string $location): ?string
    {
        $normalizedLocation = $this->normalize($location);

        if ($normalizedLocation === '') {
            return null;
        }

        $matches = [];

        foreach (config('mitra.zones', []) as $zoneKey => $zone) {
            foreach ($zone['location_keywords'] ?? [] as $keyword) {
                $normalizedKeyword = $this->normalize((string) $keyword);

                if ($normalizedKeyword !== '' && str_contains($normalizedLocation, $normalizedKeyword)) {
                    $matches[$zoneKey] = true;
                    break;
                }
            }
        }

        return count($matches) === 1 ? array_key_first($matches) : null;
    }

    private function normalize(string $value): string
    {
        $value = function_exists('mb_strtolower')
            ? mb_strtolower(trim($value), 'UTF-8')
            : strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}
