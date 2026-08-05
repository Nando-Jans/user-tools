<?php

namespace NandoJans\UserTools\Util;

class RandomPasswordGenerator
{
    public function generate(int $length = 8): string
    {
        return bin2hex(random_bytes($length));
    }
}