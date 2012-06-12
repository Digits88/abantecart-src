<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/
if (! defined ( 'DIR_CORE' )) {
	header ( 'Location: static_pages/' );
}

abstract class AController {
	protected $registry;	
	protected $instance_id;
	protected $controller;		
	protected $parent_controller;	
	protected $children = array();
	public $dispatcher;
	public $view;
	protected $languages = array();
	
	public function __construct($registry, $instance_id, $controller, $parent_controller = '') {
		$this->registry = $registry;
		$this->instance_id = $instance_id;
		$this->controller = $controller;
		$this->parent_controller = $parent_controller;
		
		//Instance of view for the controller
		$this->view = new AView($this->registry, $instance_id);		
		
		if ($this->language) {
			//initiate array of language references for current controller instance.
			//add main language to languages references
			$this->languages[] = $this->language->language_details['filename'];
			$this->loadLanguage ( $this->controller, "silent" );
		}
		//Load default model for current controller instance. Ignore if no model found  mode = silent
		$this->loadModel ( $this->controller, "silent" );

		if ($this->layout) {
			//Load Controller template and pass to view. This can be reset in controller as well
			$this->view->setTemplate( $this->layout->getBlockTemplate($this->instance_id) );			
			//Load Children from layout if any. 'instance_id', 'contorller', 'block_text_id', 'template'
			$this->children = $this->layout->getChildren($this->instance_id);
		}
	}
	
	public function __destruct() {
		if ( isset( $this->language ) ) {
			//clean up the scope
			$this->language->set_language_scope( array() );
		}
		$this->clear(); 	
	}

	// Clear funstion is public in case controller needs to be cleaned explicitly
	public function clear(){ 
		$vars = get_object_vars($this);
		foreach($vars as $key => $val) 
		{ 
   			$this->$key = null;
		}
	} 
	
	public function __get($key) {
		return $this->registry->get($key);
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
		
	//Load language and store to veiw
	public function loadLanguage ( $rt, $mode = '' ) {
		if( empty ($rt) || !method_exists( $this->language, 'load' ) ) return;
		// strip off pages or responce
		$rt = preg_replace('/^(pages|responses)\//', '', $rt);
		$this->languages[] = $rt;
		//load all tranlations to the view
		$this->view->batchAssign( $this->language->load( $rt, $mode ) );
	}

	public function loadModel ( $rt, $mode = '' ) {
		if( empty ($rt) || !method_exists( $this->load, 'model' ) ) return;
		// strip off pages or responce
		$rt = preg_replace('/^(pages|responses)\//', '', $rt);
		$this->load->model( $rt, $mode );
	}

		
	// Dispatch new controller to be ran		
	protected function dispatch($dispatch_rt, $args = array('')) {
		return new ADispatcher($dispatch_rt, $args);
	}
	// Redirect to new page
	protected function redirect($url) {
		header('Location: ' . str_replace('&amp;', '&', $url));
		die();
	}

	public function getInstance() {
		return $this->instance_id;
	}

	public function getChildren() {
		//Check if we have children in layout
		return $this->children;
	}

    public function setChildren( $children ) {
        $this->children = $children;
    }

	public function getChildrenBlocks() {
		$blocks = array();
		// Look into all blocks that are loaded from latyout database or have position set for them 
		// Hardcoded children with blocks require manual inclusion to the templates.
		foreach ($this->children as $block) {
				if ( !empty($block['position']) ) {
					array_push($blocks, $block['block_txt_id'].'_'.$block['instance_id']);
				}
		}		
		return $blocks;
	}

	// Add Child controller to be processed
	public function addChild($new_controller, $block_text_id, $new_template = '', $template_position = '' ) {
		// append child to the controller children list
		$new_block = array();
		$new_block['parent_instance_id'] = $this->instance_id;
		$new_block['instance_id'] = $block_text_id.$this->instance_id;
		$new_block['block_id'] = $block_text_id;
		$new_block['controller'] = $new_controller;
		$new_block['block_txt_id'] = $block_text_id;
		$new_block['template'] = $new_template;
		// This it to position element to the placeholder. 
		// If not set emenet will not be displayed in place holder. 
		// To use manual inclusion to parent template ignore this parameter
		$new_block['position'] = $template_position;
		array_push($this->children, $new_block );
	}

    public function processTemplate($template = '') {
        if ( !empty($template) ) {
            $this->view->setTemplate($template);
        }
        $this->view->assign("children_blocks", $this->getChildrenBlocks( ));
        $this->view->enableOutput();
    }

	public function finalize() {
		//Render the controller output in view

		// template debug
		if($this->config){
			if($this->config->get('storefront_template_debug')){
				// storefront enabling
				if(!IS_ADMIN && !isset($this->session->data['tmpl_debug']) && isset($this->request->get['tmpl_debug'])){
					$this->session->data['tmpl_debug'] = isset($this->request->get['tmpl_debug']);
				}

				if((isset($this->session->data['tmpl_debug']) && isset($this->request->get['tmpl_debug'])) && ($this->session->data['tmpl_debug'] == $this->request->get['tmpl_debug'])) {

					$block_details = $this->layout->getBlockDetails($this->instance_id);
					$excluded_blocks = array('common/head');
					//if(!empty($block_details['parent_instance_id']) && $block_details['parent_instance_id'] > 0) {
					if(!empty($this->instance_id) && (string)$this->instance_id != '0' && !in_array($block_details['controller'], $excluded_blocks)) {
						if(!empty($this->parent_controller)) {
							$args = array(	'block_id' => $this->instance_id,
											'block_controller' => $this->dispatcher->getFile(),
											'block_tpl' => $this->view->data['template_dir'] . $this->view->getTemplate(),
											'parent_id' => $this->parent_controller->instance_id,
											'parent_controller' => $this->parent_controller->dispatcher->getFile(),
											'parent_tpl' => $this->parent_controller->view->data['template_dir'] . $this->parent_controller->view->getTemplate()
											);
							$debug_wrapper = $this->dispatch('common/template_debug', array('instance_id' => $this->instance_id, 'details' => $args));
							$debug_output = $debug_wrapper->dispatchGetOutput();
							$output = trim($this->view->getOutput());
							if(!empty($output)) $output = '<div class="block_tmpl_wrapper">'.$output.$debug_output.'</div>';
							$this->view->setOutput($output);
						}
					}

				}
			}else{
				unset($this->session->data['tmpl_debug']);
			}
		}
		$this->view->render();
	}	
	
	//Set of functions to access parent controller and exchange information
	public function addToParentByName($parant_controller_name, $variable, $value){
		if ( $parant_controller_name == $this->instance_id ) {
			$this->view->append($variable, $value); 	
		} else if ( !empty ($this->parent_controller) ){
			$this->parent_controller->AddToParentByName($parant_controller_name, $variable, $value);
		} else {
        	$wrn = new AWarning( 'Call to unknown parent controller ' . $parant_controller_name . ' in ' . get_class($this) );
        	$wrn->toDebug()->toLog();			
		}
		
	}

	//Add value to direct parent
	public function addToParent($variable, $value){
		if ( !empty ($this->parent_controller) ){ 
			$this->parent_controller->view->append($variable, $value);
		} else {
        	$wrn = new AWarning( 'Parent controller called does not exist in ' . get_class($this) );
        	$wrn->toDebug()->toLog();			
		}
	}
	
	public function can_access(){
		if (!defined('IS_ADMIN') || !IS_ADMIN ) {
			return;
		}
		
		//Future stronger security permissions validation 
		//validate session token and login
		// Dispatch to login if failed
		// validate access rights for current controller or parent with $parent_controller->can_accesss()	
		// If both have no access rights dispatch to no rights page
		
		// NOTEs: Need to skip for some common controllers.		
		// Need to include this validation in constructor and break out of it if failed.
	}
	
	//Generate the URL to external help
	public function gen_help_url( $sub_key = '' ){
		if ( $this->config->get('config_help_links') != 1 ) { return; }
	
		if (!empty($sub_key)) {
			$main_key = $sub_key;
		} else {
			$main_key = str_replace('/', '_', $this->controller);
		}
		
		$url = "http://www.abantecart.com/search?areas=content&searchword=" . $main_key;
		return $url;
	}
	
}