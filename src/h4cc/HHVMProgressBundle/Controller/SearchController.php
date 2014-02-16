<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    public function formAction()
    {
        return $this->render('h4ccHHVMProgressBundle:Search:form.html.twig');
    }


    public function resultAction(Request $request)
    {
        $pattern = $request->get('pattern');

        $packages = $this->get('h4cc_hhvm_progress.repos.package_version')->findWhereNameContains($pattern);

        return $this->render('h4ccHHVMProgressBundle:Search:result.html.twig', array('packages' => $packages));
    }

}
