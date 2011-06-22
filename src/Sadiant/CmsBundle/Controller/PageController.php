<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sadiant\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sadiant\CmsBundle\Repository\PageRepository;
use Sadiant\CmsBundle\Form\PageType;
use Sadiant\CmsBundle\Entity\Page;

class PageController extends Controller
{
    /**
     * List pages
     *
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-20
     */
    public function indexAction()
    {
        // Retrieve pages
        $pages = $this->getDoctrine()->getEntityManager()->getRepository('SadiantCmsBundle:Page')->queryForMainPages()->getResult();

        return $this->render('SadiantCmsBundle:Page:index.html.twig',array('pages' => $pages));
    }

    /**
     * New page action
     * 
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function newAction()
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve EntityManager
        $em = $this->getDoctrine()->getEntityManager();
        
        // Retrieve parent page
        $parent_page = $em->getRepository('SadiantCmsBundle:Page')->findOneBy(array('id' => $request->get('id')));

        // Create new page
        $page = new Page();
        $page->setParentId($parent_page->getId());
        $page->setParent($parent_page);
        
        // Create form
        $form = $this->createForm(new PageType(), $page);
        
        // Request is post, bind and save form
        if ($request->getMethod() == 'POST')
        {
            // Bind form
            $form->bindRequest($request);

            // Check form
            if ($form->isValid())
            {
                // Perform some action, such as save the object to the database
                $page = $form->getData();
                $em->persist($page);
                $em->flush(); 

                return $this->redirect($this->generateUrl('page_edit', array('id' => $page->getId())));
            }
        }

        return $this->render(
            'SadiantCmsBundle:Page:edit.html.twig',
            array(
                'form' => $form->createView(),
                'parent_page' => $parent_page
            )
        );
    }
    
    /**
     * Edit page action
     * 
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function editAction()
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve EntityManager
        $em = $this->getDoctrine()->getEntityManager();
        
        // Retrieve page
        $page = $em->getRepository('SadiantCmsBundle:Page')->findOneBy(array('id' => $request->get('id')));
        
        // Create form
        $form = $this->createForm(new PageType(false), $page);
        
        return $this->render(
            'SadiantCmsBundle:Page:edit.html.twig',
            array(
                'form' => $form->createView(),
                'page' => $page
            )
        );
    }
    
    /**
     * Update page action
     * 
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function updateAction()
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve EntityManager
        $em = $this->getDoctrine()->getEntityManager();
        
        // Retrieve page
        $page = $em->getRepository('SadiantCmsBundle:Page')->findOneBy(array('id' => $request->get('id')));
        
        // Create form
        $form = $this->createForm(new PageType(false), $page);

        // Request is post
        if ($request->getMethod() == 'POST')
        {
            // Bind form
            $form->bindRequest($request);
 
            // Check form and save object
            if ($form->isValid())
            {
                $page = $form->getData();
                $em->persist($page);
                $em->flush();
            }
        }

        return $this->redirect($this->generateUrl('page_edit', array('id' => $page->getId())));
    }
    
    /**
     * Remove page action
     * 
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function removeAction()
    {

    }
    
    /**
     * Homepage action
     * 
     * @return string
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function homepageAction()
    {
        // Update twig loader
        $twigEnvironment = $this->get('twig');
        $oldLoader = $twigEnvironment->getLoader();
        $twigEngine = $this->get('templating');
        
        // Retrieve EntityManager
        $em = $this->getDoctrine()->getEntityManager();
        
        // Retrieve page
        $page = $em->getRepository('SadiantCmsBundle:Page')->findOneBy(array('slug' => PageRepository::SLUG_HOMEPAGE));

        $twigEnvironment->setLoader(new Twig_Loader_String());
        try
        {
          $response = $twigEngine->renderResponse($page->getContent());
          $twigEnvironment->setLoader($oldLoader);
        }
        catch (Twig_Error_Syntax $e)
        {
          $twigEnvironment->setLoader($oldLoader);
          throw $e;
        }

        return $response;
    }
}
