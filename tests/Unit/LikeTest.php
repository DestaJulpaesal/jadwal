<?php

namespace Tests\Unit;

use App\Support\Like;
use PHPUnit\Framework\TestCase;

class LikeTest extends TestCase
{
    public function test_like_wildcards_and_escape_character_are_literalized(): void
    {
        $this->assertSame('a!%!_!!!\\b', Like::escape('a%_!\\b'));
    }
}
