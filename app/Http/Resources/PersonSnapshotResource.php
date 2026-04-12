<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Helpers\ImageHelper;
use App\Models\MediaListSnapshot;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MediaListSnapshot $snapshot */
        $snapshot = $this->resource['snapshot'];

        /** @var Person|null $person */
        $person = $this->resource['entity'] ?? null;

        return [
            // Snapshot fields
            'rank' => $snapshot->rank,
            'popularity' => $snapshot->popularity,
            'snapshot_date' => $snapshot->snapshot_date?->format('Y-m-d'),
            'tmdb_id' => $snapshot->tmdb_id,
            'local_id' => $snapshot->local_id,

            // Person entity fields (null when entity not found)
            'id' => $person?->id,
            'name' => $person?->name,
            'known_for_department' => $person?->known_for_department,
            'profile_path' => $person ? ImageHelper::url($person->profile_path, 'w185') : null,
            'gender' => $person?->gender,
        ];
    }
}
