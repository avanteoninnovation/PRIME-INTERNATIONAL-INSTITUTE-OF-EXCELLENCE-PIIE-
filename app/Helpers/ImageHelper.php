<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Get image URL with fallback to placeholder
     */
    public static function getImageUrl(?string $imagePath, string $type = 'general'): string
    {
        if (!$imagePath) {
            return self::getPlaceholder($type);
        }
        
        $fullPath = public_path('assets/uploads/website/' . $imagePath);
        
        if (file_exists($fullPath)) {
            return asset('assets/uploads/website/' . $imagePath);
        }
        
        return self::getPlaceholder($type);
    }
    
    /**
     * Get placeholder image based on type
     */
    public static function getPlaceholder(string $type = 'general'): string
    {
        $placeholders = [
            'section' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"%3E%3Crect fill="%23f0f4f9" width="1200" height="400"/%3E%3Ctext x="50%" y="50%" font-size="48" fill="%23667085" text-anchor="middle" dominant-baseline="middle" font-family="Arial"%3ESection Image%3C/text%3E%3C/svg%3E',
            'item' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23f2f6fb" width="400" height="300"/%3E%3Ctext x="50%" y="50%" font-size="32" fill="%238f9096" text-anchor="middle" dominant-baseline="middle" font-family="Arial"%3ENo Image%3C/text%3E%3C/svg%3E',
            'team' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%234a90e2" width="200" height="200"/%3E%3Ccircle cx="100" cy="70" r="35" fill="%23fff"/%3E%3Cpath d="M 100 110 Q 60 110 60 140 L 140 140 Q 140 110 100 110" fill="%23fff"/%3E%3C/svg%3E',
            'page' => 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 600"%3E%3ClinearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%"%3E%3Cstop offset="0%" style="stop-color:%231a3a6b;stop-opacity:1" /%3E%3Cstop offset="100%" style="stop-color:%232d5a9e;stop-opacity:1" /%3E%3C/linearGradient%3E%3Crect fill="url(%23grad)" width="1920" height="600"/%3E%3C/svg%3E',
        ];
        
        return $placeholders[$type] ?? $placeholders['section'];
    }
    
    /**
     * Check if image file exists
     */
    public static function imageExists(?string $imagePath): bool
    {
        if (!$imagePath) {
            return false;
        }
        
        return file_exists(public_path('assets/uploads/website/' . $imagePath));
    }
}
