<?php

namespace DreamCommerce\GearmanBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DreamCommerceGearmanBundle extends Bundle
{
    public function getParent()
    {
        return 'GearmanBundle';
    }
}
