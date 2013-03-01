<?php
/**
 * @version		1.0
 * @package		Inceptive Mutliple Categories for K2
 * @author		Inceptive Design Labs - http://www.inceptive.gr
 * @copyright	Copyright (c) 2006 - 2012 Inceptive GP. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' ); 

require_once("helpers/incptvk2multiplecategories.php");

// Load the base K2 plugin class. All K2 plugins extend this class.
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/'.'components'.'/'.'com_k2'.'/'.'lib'.'/'.'k2plugin.php');

class plgK2Incptvk2multiplecategories extends K2Plugin
{
    // K2 plugin name. Used to namespace parameters.
    var $pluginName = 'incptvk2multiplecategories';
    
    // K2 human readable plugin name. This the title of the plugin users see in K2 form.
    var $pluginNameHumanReadable = 'Inceptive Multiple Categories for K2';
    
    var $plg_copyrights_start		= "\n\n<!-- Inceptive Multiple Categories for K2 Plugin (v1.0) starts here -->\n";
    var $plg_copyrights_end		= "\n<!-- Inceptive Multiple Categories for K2 Plugin (v1.0) ends here -->\n\n";

    // Constructor
    public function __construct(&$subject, $config)
    {   
        // Construct
        parent::__construct($subject, $config);
    }
	
    function onK2BeforeSetQuery(&$query)
    {	
	$mainframe = JFactory::getApplication();
	
	if($mainframe->isAdmin())
	{
	    //Filtering K2 items by K2 category
	    if(strpos($query, 'AND i.catid'))
	    {
		$pos = strpos($query, 'WHERE');
		$first = substr($query, 0 , $pos);
		$second = substr($query, $pos);

		$queryAddition = " LEFT JOIN #__k2_multiple_categories as mc ON mc.itemID = i.id ";

		$query = $first.$queryAddition.str_replace("AND i.catid", "AND mc.catID", $second);
	    }
	    
	    if(strpos($query, 'AND catid'))
	    {
		$pos = strpos($query, 'WHERE');
		$first = substr($query, 0 , $pos);
		$second = substr($query, $pos);

		$queryAddition = " LEFT JOIN #__k2_multiple_categories as mc ON mc.itemID = i.id ";

		$query = $first.$queryAddition.str_replace("AND catid", "AND mc.catID", $second);
	    }
	}
	
	if ($mainframe->isSite())
	{
	    $task = JRequest::getCmd('task');
	    $view= JRequest::getCmd('view');
	    $layout= JRequest::getCmd('layout');

	    //Presenting one category
	    if($task=='category' && $view=='itemlist')
	    {
		if(strpos($query, 'AND c.id'))
		{
		    $isCount = strpos($query, 'COUNT');
		    if($isCount != 0)
		    {
			$query = str_replace('*', 'DISTINCT i.id', $query);
		    }
		    else
		    {
			$query = str_replace('i.*', 'DISTINCT i.*', $query);
		    }
		    $pos = strpos($query, 'WHERE');
		    $first = substr($query, 0 , $pos);
		    $second = substr($query, $pos);

		    $queryAddition = " RIGHT JOIN #__k2_multiple_categories as mc ON mc.itemID = i.id ";

		    $query = $first.$queryAddition.str_replace("AND c.id", "AND mc.catID", $second);
		}
	    }
	    
	    //Presenting multiple categories
	    if($layout=='category' && $view=='itemlist')
	    {
		if(strpos($query, 'AND i.catid'))
		{
		    $isCount = strpos($query, 'COUNT');
		    if($isCount != 0)
		    {
			$query = str_replace('*', 'DISTINCT i.id', $query);
		    }
		    else
		    {
			$query = str_replace('i.*', 'DISTINCT i.*', $query);
		    }
		    $pos = strpos($query, 'WHERE');
		    $first = substr($query, 0 , $pos);
		    $second = substr($query, $pos);

		    $queryAddition = " RIGHT JOIN #__k2_multiple_categories as mc ON mc.itemID = i.id ";

		    $query = $first.$queryAddition.str_replace("AND i.catid", "AND mc.catID", $second);
		}
	    }
	}
	return;
    }	
	
    function onAfterK2Save (& $item, $isNew) {
	global $mainframe;

	$db 	= &JFactory::getDBO();

	/* Checking if some Extended fields to process */
	$request_params		= JRequest::getVar('plugins', array());
	$fields = (!empty($request_params['incptvk2multiplecategories'])) ? $request_params['incptvk2multiplecategories']: array();
	array_push($fields, $item->catid) ;
	$fields = array_unique($fields);
	
	/* Checking if the no categories were changed */
	$selectedCategories = array();
	$rows 			= &JTable::getInstance('IncptvK2MultipleCategories', 'Table');
	$retrievedCategories  = $rows->getSomeObjectsList('SELECT * FROM #__k2_multiple_categories mc WHERE mc.itemID = '.$item->id);
	foreach ($retrievedCategories as $retrievedCategory) { array_push($selectedCategories, $retrievedCategory->catID); }
	
	/* Evaluating the difference of the previous and current categories arrays */
	$dif1 = array_diff($fields,$selectedCategories);
	$dif2 = array_diff($selectedCategories,$fields);
	if(!empty($dif1) OR !empty($dif2))
	{
	    //new JTable instance to save the new added values
	    $row_action = &JTable::getInstance('IncptvK2MultipleCategories', 'Table');

	    $row_action->delete($item->id);
	    $row_action->change_key("id",$db);	
	    foreach($fields as $catID){
		$from = array(  'id' => 0,
				'itemID'  => $item->id,
				'catID'  => $catID
			      );	   
		$row_action->bind($from);

		$row_action->store();
	    }

	    if (!$row_action->check()) {
		    $mainframe->redirect('index.php?option=com_k2&view=item&cid='.$item->id, $row_action->getError(), 'error');
	    }		

	    if (!$row_action->store()) {
		    $mainframe->redirect('index.php?option=com_k2&view=items', $row_action->getError(), 'error');
	    }
	}

	return '';
    }
    
    function onRenderAdminForm (&$item, $type, $tab='') {
	if ($tab == 'other' && $type == 'item') {
	    $mainframe 		= &JFactory::getApplication();
	    
	    $selectedCategories = array();
	    
	    if($item->id != 0)
	    {
		$db 			= &JFactory::getDBO();
		$rows 			= &JTable::getInstance('IncptvK2MultipleCategories', 'Table');
		$retrievedCategories  = $rows->getSomeObjectsList('SELECT * FROM #__k2_multiple_categories mc WHERE mc.itemID = '.$item->id);
		foreach ($retrievedCategories as $retrievedCategory) { 
		    if($retrievedCategory->catID != $item->catid)
			array_push($selectedCategories, $retrievedCategory->catID);
		}
	    }
	    
	    $document 		= &JFactory::getDocument();
	    $path 		= str_replace("administrator/", "",JURI::base());
	    $plugin_folder 	= basename(dirname(__FILE__));
	    $document->addScript($path.'plugins/k2/'.$plugin_folder.'/js/incptvk2multiplecategories.js');
	    $document->addStyleSheet($path.'plugins/k2/'.$plugin_folder.'/css/style.css');

	    //Loading the appropriate language files
	    $lang = JFactory::getLanguage();
	    $languagePath = JPATH_PLUGINS.DS.'k2'.DS.'incptvk2multiplecategories';
	    $lang->load("plg_k2_incptvk2multiplecategories", $languagePath, null, false);

	    $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
	    
	    $categories_option = array();
	    $categories = $categoriesModel->categoriesTree(NUll, true, false);
	    if ($mainframe->isSite())
	    {
		JLoader::register('K2HelperPermissions', JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'helpers'.DS.'permissions.php');
		if (($task == 'add' || $task == 'edit') && !K2HelperPermissions::canAddToAll())
		{
		    for ($i = 0; $i < sizeof($categories); $i++)
		    {
			if (!K2HelperPermissions::canAddItem($categories[$i]->value) && $task == 'add')
			{
			    $categories[$i]->disable = true;
			}
			if (!K2HelperPermissions::canEditItem($item->created_by, $categories[$i]->value) && $task == 'edit')
			{
			    $categories[$i]->disable = true;
			}
			print_r($categories[$i]);
		    }
		}
	    }
	    
	    for ($i = 0; $i < sizeof($categories); $i++)
	    {
		if($categories[$i]->value == $item->catid)
		    $categories[$i]->disable = true;
	    }
	    
	    $categories_options = @array_merge($categories_option, $categories);

	    $tabIncptvMC_innerHtml = '<table class="admintable"><tbody>';
	    $tabIncptvMC_innerHtml .='<tr><td class="key">'.JText::_('PLG_K2_MC_ADDITIONAL_CATEGORIES_LABEL').'</td>';
	    $tabIncptvMC_innerHtml .='<td class="adminK2RightCol">';
	    $tabIncptvMC_innerHtml .= JHTML::_('select.genericlist', $categories_options, 'plugins[incptvk2multiplecategories][]', 'style="width:100%;" multiple="multiple" size="10"', 'value', 'text', $selectedCategories);
	    $tabIncptvMC_innerHtml .= '</td></tr>';	    
	    $tabIncptvMC_innerHtml .='</tbody></table>';
	    
	    $tabIncptvMC	=   '<li id="tabIncptvMC">
					<a href="#k2TabIncptvMC">'.JText::_('PLG_K2_MC_MULTIPLE_CATEGORIES_LABEL').'</a>
				    </li>';
	    $tabIncptvMC_content  = '<div id="k2TabIncptvMC" class="simpleTabsContent" >'.$tabIncptvMC_innerHtml.'</div>';
	    
	    echo $tabIncptvMC.$tabIncptvMC_content;
    }
    }
}
