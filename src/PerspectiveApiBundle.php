<?php

declare(strict_types=1);

namespace Freema\PerspectiveApiBundle;

use Freema\PerspectiveApiBundle\DependencyInjection\PerspectiveApiExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PerspectiveApiBundle extends Bundle
{
    public function getContainerExtension(): PerspectiveApiExtension
    {
        return new PerspectiveApiExtension();
    }
}
