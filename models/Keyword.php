<?php namespace PalPalych\AutoInterlinking\Models;

use Cache;
use Model;
use Illuminate\Cache\TaggableStore;
use Carbon\CarbonInterface;
use PalPalych\AutoInterlinking\Classes\Interlinking;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin Builder
 *
 * @property-read int $id
 *
 * @property ?string $keyword
 * @property ?string $url
 * @property bool $active
 * @property ?object $settings
 *
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class Keyword extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'palpalych_autointerlinking_keywords';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    public $dates = ['created_at', 'updated_at'];

    /**
     * @var array fillable fields
     */
    public $fillable = ['keyword', 'url', 'active', 'settings'];

    public $jsonable = ['settings'];

    public function getSetting(string $key): mixed
    {
        return $this->settings ? $this->settings[$key] ?? null : null;
    }

    /**
     * After-save handler
     */
    public function afterSave()
    {
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Interlinking::CACHE_TAG)->flush();
        } else {
            Cache::flush();
        }
    }

    /**
     * After-delete handler
     */
    public function afterDelete()
    {
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Interlinking::CACHE_TAG)->flush();
        } else {
            Cache::flush();
        }
    }
}
