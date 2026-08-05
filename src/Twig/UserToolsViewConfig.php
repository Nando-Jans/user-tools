<?php

namespace NandoJans\UserTools\Twig;

class UserToolsViewConfig
{
    public function __construct(
        public array $navButton,
        public array $details,
        public array $items,
        public array $logout,
    )
    {
    }
}