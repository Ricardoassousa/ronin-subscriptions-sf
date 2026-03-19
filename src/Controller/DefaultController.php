<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    /**
     * Displays the homepage.
     *
     * This action renders the homepage template and passes a message to the view.
     * The message is displayed on the homepage and can be customized.
     *
     * @return Response
     */
    public function homepage(): Response
    {
        return $this->render('index.html.twig');
    }

}