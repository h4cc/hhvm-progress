<?php

namespace h4cc\HHVMProgressBundle\Twig;


class HhvmExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'hhvm_progress_bundle';
    }

    public function getGlobals()
    {
        return array(
            'php_version' => phpversion(),
        );
    }
}