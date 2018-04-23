<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\LoginRedirectsBundle;

use HeimrichHannot\LoginRedirectsBundle\DependencyInjection\LoginRedirectsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoLoginRedirectsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new LoginRedirectsExtension();
    }
}
