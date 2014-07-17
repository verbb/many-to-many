<?php
namespace Craft;

class ManyToManyPlugin extends BasePlugin
{
    
    public function init()
    {
        parent::init();
        if (craft()->request->isCpRequest()) {
            craft()->templates->includeJsResource('manytomany/js/hide-input.js');
        }
    }

    public function getName()
    {
        return Craft::t('Many to Many');
    }

    public function getVersion()
    {
        return '0.1';
    }

    public function getDeveloper()
    {
        return 'Page 8';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.page-8.com';
    }
}
