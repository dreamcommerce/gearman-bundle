Gearman Bundle Wrapper
==========================

It's a wrapper on https://packagist.org/packages/mmoreram/gearman-bundle

We simply needed to process some signals provided by pcntl_signal. Original one disallowed to do so if worker was
waiting for a job.

## Installation

1. ``composer require dreamcommerce/gearman-bundle``
2. Edit ``AppKernel.php``, append these bundles:
```php
new Mmoreram\GearmanBundle\GearmanBundle(),
new DreamCommerce\GearmanBundle\DreamCommerceGearmanBundle(), 
```
3. That's all.

## Changelog

## 1.0.2
- fixed autoloader definition

## 1.0.1
- fixed version constraint stability

## 1.0
- fix for PHP 7 in related commit of ``mmoreram/gearman-bundle``; removed obsolete logic
- added a possibility to generate workers configuration for supervisord 

## 0.1.4
- cleaned-up version constraint
- bound ``mmoreram/gearman-bundle`` to exact version constraint due to the segfault in PHP 7

## 0.1.3
- added support for memory-leak protection
- fixed autoloader

## 0.1.2
- first working version