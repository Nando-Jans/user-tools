<?php

namespace NandoJans\UserTools\Service;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;

final class EnvironmentTwoFactorCondition implements TwoFactorConditionInterface
{
    /** @param list<string> $disabledEnvironments */
    public function __construct(private readonly string $environment, private readonly array $disabledEnvironments)
    {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        return !in_array($this->environment, $this->disabledEnvironments, true);
    }
}
