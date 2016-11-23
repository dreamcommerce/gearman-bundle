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

## 1.0.6
- added ``name_prefix`` to specify task name prefix (useful when single Supervisor is shared between prod/dev)
- fixed generating when no programs is defined

## 1.0.5
- fixed an issue with generating file with not configured workers

## 1.0.4
- fixed incorrect generated command

## 1.0.3
- finished messing up with repositories locations, until pull request of ``mmoreram/gearman-bundle`` is being accepted, you have to declare a overriding repository in your main ``composer.json``
```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "mmoreram/gearman-bundle",
            "version": "4.0",
            "source": {
                "url": "https://github.com/er1z/GearmanBundle",
                "type": "git",
                "reference": "master"
            }
        }
    }
],
```

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