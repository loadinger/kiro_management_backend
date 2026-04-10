<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TvShowImage;
use App\Repositories\Contracts\TvShowImageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class TvShowImageRepository extends BaseRepository implements TvShowImageRepositoryInterface
{
    /** Allowed image types to prevent invalid filter values. */
    private const ALLOWED_IMAGE_TYPES = ['poster', 'backdrop', 'logo'];

    public function __construct()
    {
        parent::__construct(new TvShowImage);
    }

    /**
     * Paginate images for a given tv show, optionally filtered by image_type.
     * tv_show_id is required to prevent full-table scans.
     * image_type whitelist: poster, backdrop, logo.
     */
    public function paginateByTvShowId(int $tvShowId, array $filters): LengthAwarePaginator
    {
        $query = TvShowImage::where('tv_show_id', $tvShowId);

        if (
            ! empty($filters['image_type'])
            && in_array($filters['image_type'], self::ALLOWED_IMAGE_TYPES, true)
        ) {
            $query->where('image_type', $filters['image_type']);
        }

        return $query->paginate(
            perPage: (int) ($filters['per_page'] ?? 20),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
