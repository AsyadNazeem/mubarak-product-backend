<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'status',
        'image_path'
    ];

    /**
     * Generate the next category ID
     *
     * @return string
     */
    public static function generateNextCategoryId()
    {
        $lastCategory = self::orderBy('category_id', 'desc')->first();

        if ($lastCategory) {
            // Extract the numeric part and increment
            $numericPart = (int) substr($lastCategory->category_id, 3);
            $nextNumeric = $numericPart + 1;
        } else {
            // Start with 1 if no categories exist
            $nextNumeric = 1;
        }

        // Format with CAT prefix and leading zeros
        return 'CAT' . str_pad($nextNumeric, 4, '0', STR_PAD_LEFT);
    }
}
