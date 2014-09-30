<?php

namespace core;

class View extends \Slim\View
{
	public function render($template, $data = null)
	{
		$_inner_view = $template.'.html';
		if ($data) {
			$data['_inner_view'] = $_inner_view;
		} else {
			$data = ['_inner_view' => $_inner_view];
		}
		return parent::render('master.html', $data);
	}

	public function getFlashValue($name)
	{
		if ($flash = $this->get('flash')) {
			return isset($flash[$name]) ? htmlspecialchars($flash[$name]) : '';
		} else {
			return '';
		}
	}
}
