<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['name', 'title', 'content', 'is_active', 'image_path'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Ubah nama menjadi slug URL yang aman (huruf kecil, dash).
     */
    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
