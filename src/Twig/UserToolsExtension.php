<?php

namespace NandoJans\UserTools\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class UserToolsExtension extends AbstractExtension implements GlobalsInterface
{

    public function __construct(
        private readonly UserToolsViewConfig $config
    )
    {
    }

    public function getGlobals(): array
    {
        return [
            'user_tools' => [
                 'nav_button' => $this->config->navButton,
                 'details' => $this->config->details,
                 'items' => $this->config->items,
                 'logout' => $this->config->logout
            ]
        ];
    }
}