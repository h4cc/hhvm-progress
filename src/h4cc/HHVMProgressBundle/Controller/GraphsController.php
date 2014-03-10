<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GraphsController extends Controller
{
    public function listAction()
    {
        return $this->render('h4ccHHVMProgressBundle:Graphs:list.html.twig');
    }
}
