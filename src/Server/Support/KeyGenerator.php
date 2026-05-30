<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Server\Support;

/**
 * Produces human-friendly license keys like `ABCD-EF23-GH45-JK67`. The alphabet
 * deliberately omits visually ambiguous characters (0/O, 1/I/L) so keys can be
 * read aloud and retyped without confusion. Randomness comes from random_int(),
 * which is cryptographically secure.
 */
final class KeyGenerator
{
    public function __construct(
        private readonly int $groups,
        private readonly int $groupSize,
        private readonly string $alphabet,
    ) {}

    public function generate(): string
    {
        $max = strlen($this->alphabet) - 1;
        $parts = [];

        for ($group = 0; $group < $this->groups; $group++) {
            $chars = '';

            for ($i = 0; $i < $this->groupSize; $i++) {
                $chars .= $this->alphabet[random_int(0, $max)];
            }

            $parts[] = $chars;
        }

        return implode('-', $parts);
    }

    public function prefix(string $key): string
    {
        $position = strpos($key, '-');

        return $position === false
            ? substr($key, 0, $this->groupSize)
            : substr($key, 0, $position);
    }
}
