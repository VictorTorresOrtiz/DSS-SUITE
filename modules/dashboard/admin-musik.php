<?php
/**
 * Module: DSS Dashboard
 * Description: Rediseño total del dashboard de wordpress
 */

class FFL_Admin_Theme_Musik
{

	function __construct()
	{
		$this->init();
	}

	function init()
	{

		$dir = dirname(__FILE__);
		require $dir . '/modules/setting/setting.php';
		require $dir . '/modules/nav/nav.php';
		require $dir . '/modules/color/color.php';
		require $dir . '/modules/login/login.php';
		require $dir . '/modules/footer/footer.php';

		$arg = array(
			'page_title' => 'DSS Admin Theme'
			,
			'menu_title' => 'DSS Dashboard'
			,
			'menu_slug' => 'admin-musik'
			,
			'setting_name' => 'admin_theme_musik_option'
			,
			'plugin_file' => __FILE__
		);

		$setting =
			new FFL_Admin_Theme_Setting($arg);
		new FFL_Admin_Theme_Nav($setting);
		new FFL_Admin_Theme_Color($setting);
		new FFL_Admin_Theme_Footer($setting);
		new FFL_Admin_Theme_Login($setting);

		require $dir . '/modules/demo/demo.php';
		new FFL_Admin_Theme_Demo($setting);

	}

}

new FFL_Admin_Theme_Musik;
