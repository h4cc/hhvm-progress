<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
    public function resultAction(Request $request)
    {
        $packages = array();
        if ($request->query->has('pattern')) {
            $pattern = $request->get('pattern');
            $packages = $this->get('h4cc_hhvm_progress.repos.package')->searchByNamePattern($pattern);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($packages, $request->query->get('page', 1), 100);

        return $this->render('h4ccHHVMProgressBundle:Search:result.html.twig', array(
            'pagination' => $pagination,
            'show_result' => (bool)$packages,
        ));
    }
}
