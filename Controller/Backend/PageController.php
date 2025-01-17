<?php

/*
 * This file is part of the Roger CMS Bundle
 *
 * (c) Theodo <contact@theodo.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Theodo\RogerCmsBundle\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Theodo\RogerCmsBundle\Repository\PageRepository;
use Theodo\RogerCmsBundle\Form\PageType;
use Theodo\RogerCmsBundle\Entity\Page;

class PageController extends Controller
{

    /**
     * List pages
     *
     * @return Response
     *
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-20
     */
    public function indexAction()
    {
        // Retrieve pages
        $pages = $this->get('roger.content_repository')->getFirstTwoLevelPages();

        return $this->render(
                 'TheodoRogerCmsBundle:Page:index.html.twig',
                 array(
                    'pages' => $pages,
                    'roger_admin_layout' => $this->container->getParameter('roger.admin.layout')
                 )
               );
    }


    /**
     * Edit page action
     *
     * @param integer $id
     * @param integer $parent_id
     *
     * @return Response
     *
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @author Romain Barberi <romainb@theodo.fr>
     * @since 2011-06-21
     */
    public function editAction($id = null, $parent_id = null)
    {
        // new page
        if (!$id) {
            $page = new Page();
            $parent_page = $this->get('roger.content_repository')->findOneById($parent_id);
            // Create the homepage
            if ($parent_page) {
                $page->setParentId($parent_page->getId());
                $page->setParent($parent_page);
            }
            else {
                $parent_page = 'homepage';
            }
        }
        // update page
        else {
            $page = $this->get('roger.content_repository')->findOneById($id);
            $parent_page = $page->getParent();
        }

        // Get all layouts
        $layouts = $this->get('roger.content_repository')->findAll('layout');
        $page_content = $page->getContent();

        // Get the current layout from the Twig content
        if (preg_match('#{% extends [\',\"]layout:(?P<layout_name>(.*))[\',\"] %}#sU', $page_content, $matches))
        {
            $layout_name = $matches['layout_name'];
        } else {
            $layout_name = null;
        }

        // Separate the different blocks from Twig
        // @TODO: this does not work with nested blocks.
        // Clean solution might be to use the Twig parser and recompile (?) blocks separately in twig
        //
        if (preg_match_all('#{% block (?P<block_name>(.*)) %}(?P<block_content>(.*)){% endblock %}#sU', $page_content, $matches))
        {
            $tabs = array_combine($matches['block_name'], $matches['block_content']);
        } else {
            $tabs = array();
        }

        // Create form
        $form = $this->createForm(new PageType(), $page);

        // Retrieve request
        $request = $this->getRequest();

        // Initialize form hasErros
        $hasErrors = false;

        // Request is post
        if ($request->getMethod() == 'POST') {

            $this->bindEditForm($form, $request);

            // Check form and save object
            if ($form->isValid())
            {
                // remove twig cached file
                $this->get('roger.caching')->invalidate('page:'.$page->getName());

                $page = $form->getData();
                $this->get('roger.content_repository')->save($page);

                $this->get('roger.caching')->warmup('page:'.$page->getName());

                // Set redirect route
                $redirect = $this->redirect($this->generateUrl('page_list'));
                if ($request->get('save-and-edit'))
                {
                    $redirect = $this->redirect($this->generateUrl('page_edit', array('id' => $page->getId())));
                }

                return $redirect;
            }
            else
            {
                $hasErrors = true;
            }
        }

        return $this->render(
            'TheodoRogerCmsBundle:Page:edit.html.twig',
            array(
                'form'        => $form->createView(),
                'page'        => $page,
                'hasErrors'   => $hasErrors,
                'parent_page' => $parent_page,
                'layouts'     => $layouts,
                'layout_name' => $layout_name,
                'tabs'        => $tabs,
                'roger_admin_layout' => $this->container->getParameter('roger.admin.layout')
            )
        );
    }

    /**
     * Remove page action
     *
     * @param integer $id
     * @return Response
     *
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-21
     */
    public function removeAction($id)
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve page
        $page = $this->get('roger.content_repository')->findOneById($id);

        // Request is post
        if ($request->getMethod() == 'POST') {
            // Delete page
            $this->get('roger.content_repository')->remove($page);

            return $this->redirect($this->generateUrl('page_list'));
        }

        return $this->render(
            'TheodoRogerCmsBundle:Page:remove.html.twig',
            array(
                'page' => $page,
                'roger_admin_layout' => $this->container->getParameter('roger.admin.layout')
            )
        );
    }

    /**
     * Expand page action
     *
     * @param integer $id
     * @return response
     *
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-23
     */
    public function expandAction($id)
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve page childrens
        $pages = $this->get('roger.content_repository')->findOneById($id)->getChildren();

        return $this->render(
            'TheodoRogerCmsBundle:Page:page-list.html.twig',
            array(
                'pages' => $pages,
                'level' => $request->get('level'),
                'roger_admin_layout' => $this->container->getParameter('roger.admin.layout')
            )
        );
    }

    /**
     * Site map action
     *
     * @param integer $id
     * @return Response
     *
     * @author Vincent Guillon <vincentg@theodo.fr>
     * @since 2011-06-23
     */
    public function siteMapComponentAction($from_id)
    {
        // Retrieve request
        $request = $this->getRequest();

        // Retrieve page
        $page = $this->get('roger.content_repository')->findOneById($from_id);

        return $this->render(
            'TheodoRogerCmsBundle:Page:site-map-component.html.twig',
            array(
                'page'  => $page,
                'level' => 0,
                'roger_admin_layout' => $this->container->getParameter('roger.admin.layout')
            )
        );
    }

    /**
     * Bind the edit form
     *
     * @param $form
     * @param $request
     *
     * @author Romain Barberi <romainb@theodo.fr>
     * @since 2011-08-11
     */
    protected function bindEditForm(&$form, $request)
    {

        $data = array_replace_recursive(
            $request->request->get($form->getName(), array()),
            $request->files->get($form->getName(), array())
        );

        /*
         * si la clef existe => on est en editions du twig brut
         * sinon on est uniquement sur l'edition des blocks
         */
        if (key_exists('content', $data)) {
            $page_content = $data['content'];
        } else {
            $page_content = '';
        }

        // Gestion du layout
        $layout_name = $request->get('page_layout', '');

        // Gestion de la suppresion du layout
        $layout_replace = ('' != $layout_name) ? "{% extends 'layout:".$layout_name."' %}" : "";

        // Maj du layout dans la page
        if (is_int(strpos($page_content, "{% extends 'layout"))) {
            $page_content = preg_replace("{% extends 'layout:(.*)' %}", $layout_replace, $page_content);
        } else {
            $page_content = $layout_replace.$page_content;
        }

        // Gestion des blocks
        $blocks = $request->get('page_block', array());

        // Maj des différent blocks contenues dans la page
        foreach( $blocks as $block_name => $block_content)
        {

            if (is_int(strpos($page_content, '{% block '.$block_name.' %}'))) {
                $page_content = preg_replace('{% block '.$block_name.' %}(.*){% endblock %}', '{% block '.$block_name.' %}'.$block_content.'{% endblock %}', $page_content);
            } else {
                $page_content .= '{% block '.$block_name.' %}'.$block_content.'{% endblock %}';
            }

        }

        $data['content'] = $page_content;

        // Bind form
        $form->bind($data);
    }
}
