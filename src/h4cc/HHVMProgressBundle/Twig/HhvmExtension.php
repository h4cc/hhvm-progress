<?php

namespace h4cc\HHVMProgressBundle\Twig;


class HhvmExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'hhvm_progress_bundle';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_php_version', function () {
                return phpversion();
            }),
        );
    }
}