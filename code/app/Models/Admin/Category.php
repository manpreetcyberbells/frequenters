<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'active_icon', 'inactive_icon', 'darkmode_active', 'darkmode_inactive'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('getChildrenRecursive');
    }

    public function getChildrenRecursive()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function getFullPath()
    {
        $names = [];
        $category = $this;
        while ($category) {
            $names[] = $category->name;
            $category = $category->parent;
        }
        return implode(' > ', array_reverse($names));
    }
}

