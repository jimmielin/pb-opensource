<?php
/**
 * Project "Iris"
 * This system is used for routing the "forum" urls to respective controllers.
 * 
 * Special thanks go to http://stackoverflow.com/questions/11463716/variable-prefixed-routing-in-cakephp/11464377
 * ...and also tigrang, who solved both my JOIN and Routing Issues.
 */
	/**
	 * Global Routing.
	 * This is a slightly "ugly" hack for, well, /pages, /users to work for GLOBAL app.
	 * Note that the below forum names are hard-coded forbidden: (processed in AppController)
	 * array("pages", "users", "faq", "global", "core", "admin")
	 */
	Router::connect(
		"/pages/*",
		array(
			"forum" => "global",
			"controller" => "pages",
			"action" => "display"
		),
		array(
		)
	);

	Router::connect(
		"/users/:action/*",
		array(
			"forum" => "global",
			"controller" => "users"
		),
		array(
			"pass" => array("forum")
		)
	);

	/**
	 * Per-Forum Routing.
	 * @since 10009: Added a /:forum/ catch-all index page
	 * 
	 */
	Router::connect(
		"/:forum/:controller/:action/*",
		array(),
		array("pass" => array("forum"))
	);

	Router::connect(
		"/:forum/favicon.ico",
		array(
			"forum" => "global",
			"controller" => "pages",
			"action" => "display",
			"404"
		)
	);

	Router::connect(
		"/:forum/:controller",
		array('action' => 'index'),
		array("pass" => array("forum"))
	); // fix on 2012.8.23, tigrang, http://stackoverflow.com/questions/12069021/cakephp-routing-missingcontrollerexception
 
	Router::connect(
		"/:forum",
		array(
			"controller" => "forums",
			"action" => "index"
		),
		array("pass" => array("forum"))
	);

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 *
 * Modified 2012.7.13 Jimmie Lin: Fix this up for compatibility with our custom AppController
 */
	Router::connect(
		"/",
		array(
			"forum" => "global",
			"controller" => "pages",
			"action" => "display",
			"home"
		)//,
		//array("pass" => array("forum"))
	);

/**
 * Load all plugin routes.  See the CakePlugin documentation on 
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
