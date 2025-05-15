<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sub_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sub_category_id',
        'category_id',
        'name',
        'description',
        'status',
        'image_path'
    ];

    /**
     * Get the category that owns the subcategory.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    /**
     * Get the products for the subcategory.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'sub_category_id', 'sub_category_id');
    }

    /**
     * Generate the next subcategory ID
     *
     * @return string
     */
    public static function generateNextSubCategoryId()
    {
        $latest = self::latest('sub_category_id')->first();

        if (!$latest) {
            return 'SUB0001';
        }

        // Extract numeric part from 'SUB0001'
        $number = (int) str_replace('SUB', '', $latest->sub_category_id);

        // Increment and format
        $nextNumber = $number + 1;
        return 'SUB' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
