<?php

namespace App\Enums;

enum PostType: string
{
    case News = 'news';
    case Blog = 'blog';

    public function label(): string
    {
        return match ($this) {
            self::News => 'News',
            self::Blog => 'Blog',
        };
    }

    /** Badge pill on dark cards: News = orange, Blog / Meta-Vibe = pastell-blau. */
    public function badgeStyle(): string
    {
        return match ($this) {
            self::News => 'background: rgba(255,122,31,0.2); border: 1px solid rgba(255,149,77,0.65); color: #fff7ed; box-shadow: 0 0 20px -6px rgba(255,122,31,0.55);',
            self::Blog => 'background: rgba(147,197,253,0.14); border: 1px solid rgba(125,180,250,0.5); color: #e0f2fe; box-shadow: 0 0 18px -8px rgba(96,165,250,0.35);',
        };
    }
}
