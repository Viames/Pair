<?php

/**
 * @version	$Id$
 * @author	Viames Marino
 * @package	VMS
 */

use VMS\Breadcrumb;
use VMS\View;
use VMS\Widget;

class TemplatesViewNew extends View {

	public function render() {

		$this->layout = 'new';
		
		$breadcrumb = Breadcrumb::getInstance();
		$breadcrumb->addPath('Nuovo template', 'templates/new');

		$this->app->activeMenuItem	= 'templates/default';
		$this->app->pageTitle		= $this->lang('NEW_TEMPLATE');
		
		$widget = new Widget();
		$widget = new Widget();
		$this->app->breadcrumbWidget = $widget->render('breadcrumb');
		
		$widget = new Widget();
		$this->app->sideMenuWidget = $widget->render('sideMenu');
		
		$form = $this->model->getTemplateForm();
		$this->assign('form', $form);

	}
	
}