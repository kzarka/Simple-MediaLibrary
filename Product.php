<?php

namespace Modules\Ecommerce\Models;

use App\Models\Media\ShouldMedia;
use App\Models\Media\MediaModelTrait;
use App\Models\Media\MediaModelAvatarTrait;
use App\Models\BaseModel;
use Modules\Page\Models\Page;

class Product extends BaseModel implements ShouldMedia
{
    use MediaModelTrait, MediaModelAvatarTrait;

    const PRODUCT_GALLERY_COLLECTIONS = 'gallery';

    public static $mediaConfigs = [
        'table' => 'image_collections',
        'path_prefix' => 'images/products',
        'owner_key' => 'id',
        'foreign_key' => 'fk_id',
        'fall_back' => '/assets/images/user/default.png',
        'conversions' => [
            'avatar' => ['thumbnail', 'small_thumbnail'],
            self::PRODUCT_GALLERY_COLLECTIONS => ['thumbnail', 'small_thumbnail'],
        ]
    ];

    protected $table = "products";

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name', 'slug', 'details', 'description', 'featured', 'status', 'seo_meta'
    ];

    protected static function boot() {
        parent::boot();

        static::deleting(function($model) {
            $model->variants()->each(function($variant) {
                $variant->variantValues()->delete();
            });
            
            $model->variants()->delete();
        });
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'product_category_pivot', 'product_id', 'cat_id');
    }

    public function reviews() {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function getGallery()
    {
        return $this->getMediaItems(self::PRODUCT_GALLERY_COLLECTIONS);
    }

    public function getGalleryUniqPaths()
    {
        return $this->getMediaUniqPaths(self::PRODUCT_GALLERY_COLLECTIONS);
    }

    public function uploadGallery($fileUpload, $filename = null)
    {
        return $this->addMediaFromFileUpload($fileUpload, self::PRODUCT_GALLERY_COLLECTIONS, $filename);
    }

    public function removeGalleryByUniqPath($path)
    {
        return $this->removeMediaByUniqPath($path);
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'product_option_pivot', 'product_id', 'product_option_id');
    }

    public function variants() {
        return $this->hasMany(Variant::class, 'product_id');
    }

    public function getUrlAttribute($value) {
        $category = $this->categories()->skip(1)->first() ?? $this->categories()->first();
        return url('') . '/' . Page::getProductPath() . '/' . ($category ? $category->slug : Category::getDefaultCategorySlug()) . '/' . $this->slug;
    }

    public function getPriceRangeAttribute($value) {
        if($this->min_price === $this->max_price) return number_format($this->min_price) . '₫';
        return number_format($this->min_price) . '₫' . ' ~ ' . number_format($this->max_price) . '₫';
    }

    public function getCategory() {
        return $this->categories()->first() ?? Category::getDefaultCategory();
    }
}
