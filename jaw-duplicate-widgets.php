<?php
/*
Plugin Name: JAW Duplicate Widgets
Plugin URI: http://justaddwater.dk/wordpress-plugins/
Description: Makes it possible to duplicate a widget so it can be used more than once (i.e. in two different sidebars or two times in the same sidebar). Requires the <a href="http://automattic.com/code/widgets/">Sidebar Widgets plugin</a> from Automattic.
Version: 1.0.2
Author: Thomas Watson Steen
Author URI: http://justaddwater.dk/
*/

class JAWDuplicateWidgets
{

	function register()
	{
		// get a list of all the duplicate widgets
		$options = get_option('jaw-duplicatewidget');
		if(is_array($options) && function_exists("register_sidebar_widget"))
			foreach($options as $widget)
				// for each widget traverse though its duplicates
				foreach($widget as $duplicate)
					if(is_array($duplicate))
					{
						array_push($duplicate['params'], "jaw_is_duplicate");
						$params = array_merge(array($duplicate['name'], $duplicate['callback']), $duplicate['params']);
						call_user_func_array("register_sidebar_widget", $params);
					}
	}

	function setup()
	{
		global $registered_widgets;
		$options = $newoptions = get_option('jaw-duplicatewidget');

		if (isset($_POST['jaw-duplicatewidget-duplicate']))
		{
			$id = $_POST['jaw-duplicatewidget'];
			
			// if this widget isn't currently duplicated
			if(empty($newoptions[$id]))
				$newoptions[$id] = array();
				
			// run through each and every registered widget
			foreach($registered_widgets as $name => $widget)
				// this this is the widget we are trying to duplicate...
				if($widget['id'] == $id)
				{
					// build up the new name (basically "oldname (copy X)")
					$widget['name'] = $name . ' (' . __('copy') . ' ' . (count($newoptions[$id]) + 1) . ')';
					$dup_id = sanitize_title($widget['name']); // guess the new widget id
					// add the duplicate widget to the list of duplicates
					$newoptions[$id][$dup_id] = $widget;
				}
		}
		else if (isset($_POST['jaw-duplicatewidget-remove']))
		{
			$ids = $_POST['jaw-duplicatewidget-removeid'];
			
			if(is_array($ids) && is_array($newoptions))
				foreach($ids as $id)
					foreach($newoptions as $widget_id => $widget)
						foreach($widget as $dup_id => $duplicate)
							if(is_array($duplicate) && $dup_id == $id)
							{
								if(function_exists("unregister_sidebar_widget"))
									unregister_sidebar_widget($duplicate['name']);
								unset($newoptions[$widget_id][$dup_id]);
								if(count($newoptions[$widget_id]) == 0)
									unset($newoptions[$widget_id]);
								if(count($newoptions) == 0)
									unset($newoptions);
							}
		}

		// if a widget has been duplicated or removed...
		if ($options != $newoptions)
		{
			// save the changes...
			$options = $newoptions;
			update_option('jaw-duplicatewidget', $options);
		}

		// in the end: register all duplicate widgets (also newly added)
		JAWDuplicateWidgets::register();
	}
	
	function page() 
	{
		global $registered_widgets;
		$options = get_option('jaw-duplicatewidget');

		// build an index of the duplicated widgets
		$dups = array();
		if(is_array($options))
			foreach($options as $duplicated_widget)
				foreach($duplicated_widget as $dup_id => $duplicate)
					$dups[$dup_id] = true;
?>
		<div class="wrap">
			<form method="post">
				<h2>Duplicate Widgets</h2>
				<p style="line-height: 30px;"><?php _e('Whitch widget would you like to duplicate?'); ?>
				<select id="jaw-duplicatewidget" name="jaw-duplicatewidget">
<?php
		// check against the dup-index and output all widgets that are not duplicated
		foreach ($registered_widgets as $name => $widget)
		{
			if(!$dups[$widget['id']])
				print("<option value=\"{$widget['id']}\">" . __($name) . "</option>\n");
		}
?>
				</select>
				<span class="submit">
					<input type="submit" name="jaw-duplicatewidget-duplicate" id="jaw-duplicatewidget-duplicate" value="<?php _e('Duplicate'); ?>" />
				</span>
				</p>
<?php
		// if there are any duplicated widgets, list them
		if(is_array($options))
		{
?>
				<p style="line-height: 30px;"><?php _e('Whitch widget(s) would you like to remove?'); ?></p>
				<p style="padding: 0 15px 5px">
<?php
			foreach ($options as $widget)
				foreach ($widget as $id => $duplicate)
					print('<input type="checkbox" name="jaw-duplicatewidget-removeid[]" value="' . $id . '" id="' . $id . '"><label for="' . $id . '"> ' . __($duplicate['name']) . "</label><br />\n");
?>
				</p><p>
				<span class="submit">
					<input type="submit" name="jaw-duplicatewidget-remove" id="jaw-duplicatewidget-remove" value="<?php _e('Remove'); ?>" />
				</span>
				</p>
<?php
		}
?>
			</form>
		</div>
<?php
	}
	
	function load()
	{
		add_action('sidebar_admin_setup', array('JAWDuplicateWidgets', 'setup'));
		add_action('sidebar_admin_page', array('JAWDuplicateWidgets', 'page'));
	}

}

add_action('plugins_loaded', array('JAWDuplicateWidgets', 'load'));

if(!function_exists('can_access_admin_page'))
	add_action('plugins_loaded', array('JAWDuplicateWidgets', 'register'));

?>
