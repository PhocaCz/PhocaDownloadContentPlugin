<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
use Joomla\CMS\HTML\HTMLHelper;


class plgContentPhocaDownload extends JPlugin
{
	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onContentPrepare($context, &$article, &$params, $page = 0) {

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		$app 	= JFactory::getApplication();
		$view	= $app->input->get('view');
		if ($view == 'tag') { return; }





		// Include Phoca Download
		if (!JComponentHelper::isEnabled('com_phocadownload', true)) {
			echo '<div class="alert alert-danger">Phoca Download Error: Phoca Download component is not installed or not published on your system</div>';
			return;
		}

		if (! class_exists('PhocaDownloadLoader')) {
			require_once( JPATH_ADMINISTRATOR.'/components/com_phocadownload/libraries/loader.php');
		}
		phocadownloadimport('phocadownload.utils.settings');
		phocadownloadimport('phocadownload.path.path');
		phocadownloadimport('phocadownload.path.route');
		phocadownloadimport('phocadownload.file.file');
		phocadownloadimport('phocadownload.utils.utils');
		phocadownloadimport('phocadownload.render.layout');
		phocadownloadimport('phocadownload.ordering.ordering');
		phocadownloadimport('phocadownload.render.renderfront');



		$document		= JFactory::getDocument();
		$db 			= JFactory::getDBO();
		$iSize			= $this->params->get('icon_size', 32);
		$iMime			= $this->params->get('file_icon_mime', 0);
		$component		= 'com_phocadownload';
		$paramsC		= JComponentHelper::getParams($component) ;
		$ordering		= $paramsC->get( 'file_ordering', 1 );
		$display_bootstrap3_layout		= $paramsC->get( 'display_bootstrap3_layout', 0 );

        $lang = JFactory::getLanguage();
        //$lang->load('com_phocadownload.sys');
        $lang->load('com_phocadownload');


		// Start Plugin
		$regex_one		= '/({phocadownload\s*)(.*?)(})/si';
		$regex_all		= '/{phocadownload\s*.*?}/si';
		$matches 		= array();
		$count_matches	= preg_match_all($regex_all,$article->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);




		// Start if count_matches
		if ($count_matches != 0) {

			HTMLHelper::_('stylesheet', 'media/com_phocadownload/css/main/phocadownload.css', array('version' => 'auto'));
			HTMLHelper::_('stylesheet', 'media/plg_content_phocadownload/css/phocadownload.css', array('version' => 'auto'));

			$l = new PhocaDownloadLayout();

			// Start CSS
			$renderedBootstrapModal = 0;
			for($i = 0; $i < $count_matches; $i++) {



				$view				= '';
				$id					= '';
				$text				= '';
				$target 			= '';
				$playerwidth		= $paramsC->get( 'player_width', 328 );
				$playerheight		= $paramsC->get( 'player_height', 200 );
				$previewwidth		= $paramsC->get( 'preview_width', 640 );
				$previewheight		= $paramsC->get( 'preview_height', 480 );
				$playerheightmp3	= $paramsC->get( 'player_mp3_height', 30 );
				$url				= '';
				$youtubewidth		= 448;
				$youtubeheight		= 336;
				$fileView			= $paramsC->get( 'display_file_view', 0 );
				$previewWindow 		= $paramsC->get( 'preview_popup_window', 0 );
				$playWindow 		= $paramsC->get( 'play_popup_window', 0 );
				$limit				= 5;
				//$ordering set in header;


				// Get plugin parameters
				$phocadownload	= $matches[0][$i][0];
				preg_match($regex_one,$phocadownload,$phocadownload_parts);
				$parts			= explode("|", $phocadownload_parts[2]);
				$values_replace = array ("/^'/", "/'$/", "/^&#39;/", "/&#39;$/", "/<br \/>/");


				foreach($parts as $key => $value) {
					$values = explode("=", $value, 2);

					foreach ($values_replace as $key2 => $values2) {
						$values = preg_replace($values2, '', $values);
					}

					// Get plugin parameters from article
						 if($values[0]=='view')				{$view				= $values[1];}
					else if($values[0]=='id')				{$id				= $values[1];}
					else if($values[0]=='text')				{$text				= $values[1];}
					else if($values[0]=='target')			{$target			= $values[1];}
					else if($values[0]=='playerwidth')		{$playerwidth		= (int)$values[1];}
					else if($values[0]=='playerheight')		{$playerheight		= (int)$values[1];}
					else if($values[0]=='playerheightmp3')	{$playerheightmp3	= (int)$values[1];}

					else if($values[0]=='previewwidth')		{$previewwidth		= (int)$values[1];}
					else if($values[0]=='previewheight')	{$previewheight		= (int)$values[1];}

					else if($values[0]=='youtubewidth')		{$youtubewidth		= (int)$values[1];}
					else if($values[0]=='youtubeheight')	{$youtubeheight		= (int)$values[1];}

					else if($values[0]=='previewwindow')	{$previewWindow		= (int)$values[1];}
					else if($values[0]=='playwindow')		{$playWindow		= (int)$values[1];}
					else if($values[0]=='limit')			{$limit				= (int)$values[1];}

					else if($values[0]=='url')				{$url				= $values[1];}
					else if($values[0]=='ordering')			{$ordering			= (int)$values[1];}

				}

				switch($target) {
					case 'b':
						$targetOutput = 'target="_blank" ';
					break;
					case 't':
						$targetOutput = 'target="_top" ';
					break;
					case 'p':
						$targetOutput = 'target="_parent" ';
					break;
					case 's':
						$targetOutput = 'target="_self" ';
					break;
					default:
						$targetOutput = '';
					break;
				}

				$output = '';
				/*
				//Itemid
				$menu 		=& JSite::getMenu();
				$itemSection= $menu->getItems('link', 'index.php?option=com_phocadownload&view=sections');
				if(isset($itemSection[0])) {
					$itemId = $itemSection[0]->id;
				} else {
					$itemId = JRequest::getVar('Itemid', 1, 'get', 'int');
				}
				*/
				switch($view) {
					/*
					// - - - - - - - - - - - - - - - -
					// SECTIONS
					// - - - - - - - - - - - - - - - -
					case 'sections':
						if ($text !='') {
							$textOutput = $text;
						} else {
							$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_SECTIONS');
						}

						$link = PhocaDownloadRoute::getSectionsRoute();

						$output .= '<div class="phocadownloadsections'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
					break;

					// - - - - - - - - - - - - - - - -
					// SECTION
					// - - - - - - - - - - - - - - - -
					case 'section':
						if ((int)$id > 0) {
							$query = 'SELECT a.id, a.title, a.alias,'
							. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
							. ' FROM #__phocadownload_sections AS a'
							. ' WHERE a.id = '.(int)$id;

							$db->setQuery($query);
							$item = $db->loadObject();

							if (isset($item->id) && isset($item->slug)) {

								if ($text !='') {
									$textOutput = $text;
								} else if (isset($item->title) && $item->title != '') {
									$textOutput = $item->title;
								} else {
									$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_SECTION');
								}
								$link = PhocaDownloadRoute::getSectionRoute($item->id, $item->alias);
								// 'index.php?option=com_phocadownload&view=section&id='.$item->slug.'&Itemid='. $itemId

								$output .= '<div class="phocadownloadsection'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
							}
						}
					break;
					*/

					// - - - - - - - - - - - - - - - -
					// CATEGORIES
					// - - - - - - - - - - - - - - - -
					case 'categories':
						if ($text !='') {
							$textOutput = $text;
						} else {
							$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_CATEGORIES');
						}

						$link = PhocaDownloadRoute::getCategoriesRoute();

						$output .= '<div class="phocadownloadcategories'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
					break;

					// - - - - - - - - - - - - - - - -
					// CATEGORY
					// - - - - - - - - - - - - - - - -
					case 'category':
						if ((int)$id > 0) {
							$query = 'SELECT a.id, a.title, a.alias,'
							. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'
							. ' FROM #__phocadownload_categories AS a'
							. ' WHERE a.id = '.(int)$id;

							$db->setQuery($query);
							$item = $db->loadObject();

							if (isset($item->id) && isset($item->slug)) {

								if ($text !='') {
									$textOutput = $text;
								} else if (isset($item->title) && $item->title != '') {
									$textOutput = $item->title;
								} else {
									$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_CATEGORY');
								}
								$link = PhocaDownloadRoute::getCategoryRoute($item->id, $item->alias);
								//'index.php?option=com_phocadownload&view=category&id='.$item->slug.'&Itemid='. $itemId
								$output .= '<div class="phocadownloadcategory'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
							}

						}
					break;


					// - - - - - - - - - - - - - - - -
					// FILELIST
					// - - - - - - - - - - - - - - - -
					case 'filelist':

						$fileOrdering 		= PhocaDownloadOrdering::getOrderingText($ordering, 3);

						$query = 'SELECT a.id, a.title, a.alias, a.filename_play, a.filename_preview, a.link_external, a.image_filename, a.filename, c.id as catid, a.confirm_license, c.title as cattitle, c.alias as catalias,'
						. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug,'
						. ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug'
						. ' FROM #__phocadownload AS a'
						. ' LEFT JOIN #__phocadownload_categories AS c ON a.catid = c.id';

						if ((int)$id > 0) {
							$query .= ' WHERE c.id = '.(int)$id;
							//$query .= ' WHERE c.id = '.(int)$id . ' AND a.published = 1 AND a.approved = 1';
						} else {
							//$query .= ' WHERE a.published = 1 AND a.approved = 1';
						}

						$query .= ' ORDER BY '.$fileOrdering;
						$query .= ' LIMIT 0, '.(int)$limit;

						$db->setQuery($query);
						$items = $db->loadObjectList();

						if (!empty($items)) {
							$output .= '<div class="phocadownloadfilelist">';
							foreach ($items as $item) {
								$imageFileName = $l->getImageFileName($item->image_filename, $item->filename, 3, (int)$iSize);

								if (isset($item->id) && isset($item->slug) && isset($item->catid) && isset($item->catslug)) {

									if ($text !='') {
										$textOutput = $text;
									} else if (isset($item->title) && $item->title != '') {
										$textOutput = $item->title;
									} else {
										$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_FILE');
									}

									if ((isset($item->confirm_license) && $item->confirm_license > 0) || $fileView == 1) {
										$link = PhocaDownloadRoute::getFileRoute($item->id,$item->catid,$item->alias, $item->catalias,0, 'file');

										if ($iMime == 1) {
											$output .= '<div class="pd-filename phocadownloadfilelistitem phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'><a href="'. JRoute::_($link).'" '. $targetOutput.'>'. $textOutput.'</a></div></div>';
										} else {
											$output .= '<div class="phocadownloadfilelist'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
										}

									} else {
										if ($item->link_external != '') {
											$link = $item->link_external;
										} else {
											$link = PhocaDownloadRoute::getFileRoute($item->id,$item->catid,$item->alias,$item->catalias, 0, 'download');
										}

										if ($iMime == 1) {
											$output .= '<div class="pd-filename phocadownloadfilelistitem phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'><a href="'. JRoute::_($link).'" '. $targetOutput.'>'. $textOutput.'</a></div></div>';
										} else {
											$output .= '<div class="phocadownloadfilelist'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
										}

									}

								}
							}
							$output .= '</div>';

						}
					break;





					// - - - - - - - - - - - - - - - -
					// FILE
					// - - - - - - - - - - - - - - - -
					case 'file':
					case 'fileplay':
					case 'fileplaylink':
					case 'filepreviewlink':
						if ((int)$id > 0) {
							$query = 'SELECT a.id, a.title, a.alias, a.filename_play, a.filename_preview, a.link_external, a.image_filename, a.filename, c.id as catid, a.confirm_license, c.title as cattitle, c.alias as catalias,'
							. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug,'
							. ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(\':\', c.id, c.alias) ELSE c.id END as catslug'
							. ' FROM #__phocadownload AS a'
							. ' LEFT JOIN #__phocadownload_categories AS c ON a.catid = c.id'
							. ' WHERE a.id = '.(int)$id;

							$db->setQuery($query);
							$item = $db->loadObject();

							if (isset($item->id) && isset($item->slug) && isset($item->catid) && isset($item->catslug)) {

								if ($text !='') {
									$textOutput = $text;
								} else if (isset($item->title) && $item->title != '') {
									$textOutput = $item->title;
								} else {
									if ($view == 'fileplay') {
										$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_PLAY_FILE');
									} else {
										$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_DOWNLOAD_FILE');
									}
								}

								$imageFileName = $l->getImageFileName($item->image_filename, $item->filename, 3, (int)$iSize);

								// - - - - -
								// PLAY
								// - - - - -
								if ($view == 'fileplay') {
									$play		= 1;
									$fileExt	= '';
									$filePath	= PhocaDownloadPath::getPathSet('fileplay');

									$filePath	= str_replace ( '../', JURI::base(true).'/', $filePath['orig_rel_ds']);
									if (isset($item->filename_play) && $item->filename_play != '') {
										$fileExt = PhocaDownloadFile::getExtension($item->filename_play);
										$canPlay	= PhocaDownloadFile::canPlay($item->filename_play);
										if ($canPlay) {
											$tmpl['playfilewithpath']	= $filePath . $item->filename_play;
											$tmpl['playerpath']			= JURI::base().'media/com_phocadownload/js/flowplayer/';
										} else {
											$output .= JText::_('PLG_CONTENT_PHOCADOWNLOAD_NO_CORRECT_FILE_FOR_PLAYING_FOUND');
											$play = 0;
										}
									} else {
										$output .= JText::_('PLG_CONTENT_PHOCADOWNLOAD_NO_FILE_FOR_PLAYING_FOUND');
										$play = 0;
									}

									if ($play == 1) {

                                        if ($fileExt == 'mp3') {
                                            $output .=  '<audio width="'.$playerwidth.'" height="'.$playerheight.'" style="margin-top: 10px;" controls>';
                                            $output .=  '<source src="'.$tmpl['playfilewithpath'].'" type="video/mp4">';
                                            $output .=  JText::_('COM_PHOCADOWNLOAD_BROWSER_DOES_NOT_SUPPORT_AUDIO_VIDEO_TAG');
                                            $output .=  '</audio>'. "\n";
                                        } else if ($fileExt == 'mp4') {
                                            $output .=  '<video width="'.$playerwidth.'" height="'.$playerheight.'" style="margin-top: 10px;" controls>';
                                            $output .=  '<source src="'.$tmpl['playfilewithpath'].'" type="video/mp4">';
                                            $output .=  JText::_('COM_PHOCADOWNLOAD_BROWSER_DOES_NOT_SUPPORT_AUDIO_VIDEO_TAG');
                                            $output .=  '</video>'. "\n";
                                        } else if ($fileExt == 'ogg') {
                                            $output .=  '<audio width="'.$playerwidth.'" height="'.$playerheight.'" style="margin-top: 10px;" controls>';
                                            $output .=  '<source src="'.$tmpl['playfilewithpath'].'" type="audio/ogg">';
                                            $output .=  JText::_('COM_PHOCADOWNLOAD_BROWSER_DOES_NOT_SUPPORT_AUDIO_VIDEO_TAG');
                                            $output .=  '</audio>'. "\n";
                                        } else if ($fileExt == 'ogv') {
                                            $output .=  '<video width="'.$playerwidth.'" height="'.$playerheight.'" style="margin-top: 10px;" controls>';
                                            $output .=  '<source src="'.$tmpl['playfilewithpath'].'" type="video/ogg">';
                                            $output .=  JText::_('COM_PHOCADOWNLOAD_BROWSER_DOES_NOT_SUPPORT_AUDIO_VIDEO_TAG');
                                            $output .=  '</video>'. "\n";
                                        }

										//Correct MP3
									/*	$tmpl['filetype']		= '';
										if ($fileExt == 'mp3') {
											$tmpl['filetype'] 	= 'mp3';
											$playerheight		= $playerheightmp3;
										}
										$versionFLP 	= '3.2.2';
										$versionFLPJS 	= '3.2.2';

										//Flow Player

										$document->addScript($tmpl['playerpath'].'flowplayer-'.$versionFLPJS.'.min.js');

										$output .= '<div style="text-align:center;margin: 10px auto">'. "\n"
												  .'<div style="margin: 0 auto;text-align:center; width:'. $playerwidth.'px"><a href="'. $tmpl['playfilewithpath'].'"  style="display:block;width:'. $playerwidth.'px;height:'. $playerheight.'px" id="pdplayer'.$i.'"></a>'. "\n";

										if ($tmpl['filetype'] == 'mp3') {
											$output .= '<script type="text/javascript">'. "\n"
											.'window.addEvent("domready", function() {'. "\n"


											.'flowplayer("pdplayer'.$i.'", "'.$tmpl['playerpath'].'flowplayer-'.$versionFLP.'.swf",'
											.'{ ' . "\n"
											.' clip: { '. "\n"
											.'		url: \''.$tmpl['playfilewithpath'].'\','. "\n"
											.'		autoPlay: false'  . "\n"
										//	.'		autoBuffering: true' . "\n"
											.'	}, '. "\n"
											.'	plugins: { '. "\n"
											.'		controls: { ' . "\n"
											.'			fullscreen: false, '. "\n"
											.'			height: '. $playerheight . "\n"
											.'		} ' . "\n"
											.'	} '. "\n"
											.'} '. "\n"
											.');'. "\n"

											.'});'
											.'</script>'. "\n";
										} else {

											$output .= '<script type="text/javascript">'. "\n"
											.'window.addEvent("domready", function() {'. "\n"

											.'flowplayer("pdplayer'.$i.'", "'. $tmpl['playerpath'].'flowplayer-'.$versionFLP.'.swf",'. "\n"
											.'{ ' . "\n"
											.' clip: { '. "\n"
											.'		url: \''.$tmpl['playfilewithpath'].'\','. "\n"
											.'		autoPlay: false,'  . "\n"
											.'		autoBuffering: true' . "\n"
											.'	}, '. "\n"
											.'} '. "\n"
											.');'. "\n"

											.'});'
											.'</script>'. "\n";
										}*/

										//$output .= '</div></div>'. "\n";
									}

								} else if ($view == 'fileplaylink') {

									// PLAY - - - - - - - - - - - -
									$windowWidthPl 				= (int)$playerwidth + 30;
									$windowHeightPl 			= (int)$playerheight + 30;
									$windowHeightPlMP3 			= (int)$playerheightmp3 + 30;
									//$playWindow 	= $paramsC->get( 'play_popup_window', 0 );
									if ($playWindow == 1) {
										$buttonPl = new JObject();
										$buttonPl->set('methodname', 'js-button');
										$buttonPl->set('options', "window.open(this.href,'win2','width=".$windowWidthPl.",height=".$windowHeightPl.",scrollbars=yes,menubar=no,resizable=yes'); return false;");
										$buttonPl->set('optionsmp3', "window.open(this.href,'win2','width=".$windowWidthPl.",height=".$windowHeightPlMP3.",scrollbars=yes,menubar=no,resizable=yes'); return false;");
									} else {

										if ($display_bootstrap3_layout > 0) {

											// BOOTSTRAP
											$buttonPl = new JObject();
											$buttonPl->set('name', 'image');
											$buttonPl->set('modal', true);
											$buttonPl->set('methodname', 'modal-button');
											$buttonPl->set('options', ' data-type="document" data-width-dialog="'.$windowWidthPl.'" data-height-dialog="'.$windowHeightPl.'"');
											$buttonPl->set('optionsmp3', ' data-type="document" data-width-dialog="'.$windowWidthPl.'" data-height-dialog="'.($windowHeightPlMP3 + 50) .'"');
											$buttonPl->set('optionsimg', 'data-type="image"');
											$bootstrapModal = PhocaDownloadRenderFront::bootstrapModalHtml('phModalPlay' , JText::_('COM_PHOCADOWNLOAD_PLAY'));

											if ($renderedBootstrapModal == 0) {
												PhocaDownloadRenderFront::renderBootstrapModalJs('.pd-modal-button');
												$renderedBootstrapModal = 1;
											}

										} else {
											Joomla\CMS\HTML\HTMLHelper::_('behavior.modal', 'a.modal-button');
											$document->addCustomTag( "<style type=\"text/css\"> \n"
										." #sbox-window.phocadownloadplaywindow   {background-color:#fff;padding:2px} \n"
										." #sbox-overlay.phocadownloadplayoverlay  {background-color:#000;} \n"
										." </style> \n");
											$buttonPl = new JObject();
											$buttonPl->set('name', 'image');
											$buttonPl->set('modal', true);
											$buttonPl->set('methodname', 'modal-button');
											$buttonPl->set('options', "{handler: 'iframe', size: {x: ".$windowWidthPl.", y: ".$windowHeightPl."}, overlayOpacity: 0.7, classWindow: 'phocadownloadplaywindow', classOverlay: 'phocadownloadplayoverlay'}");
											$buttonPl->set('optionsmp3', "{handler: 'iframe', size: {x: ".$windowWidthPl.", y: ".$windowHeightPlMP3."}, overlayOpacity: 0.7, classWindow: 'phocadownloadplaywindow', classOverlay: 'phocadownloadplayoverlay'}");

										}
									}
									// - - - - - - - - - - - - - - -

									$fileExt	= '';
									$filePath	= PhocaDownloadPath::getPathSet('fileplay');

									$filePath	= str_replace ( '../', JURI::base(true).'/', $filePath['orig_rel_ds']);
									if (isset($item->filename_play) && $item->filename_play != '') {
										$fileExt = PhocaDownloadFile::getExtension($item->filename_play);


										$canPlay	= PhocaDownloadFile::canPlay($item->filename_play);
										if ($canPlay) {
											// Special height for music only
											$buttonPlOptions = $buttonPl->options;

											if ($fileExt == 'mp3') {
												$buttonPlOptions = $buttonPl->optionsmp3;

											}
											/*if ($text == '') {
												$text = JText::_('PLG_CONTENT_PHOCADOWNLOAD_PLAY');
											}*/

											if ($text !='') {
												$textOutput = $text;
											//} else if (isset($item->title) && $item->title != '') {
											//	$textOutput = $item->title;
											} else {
												$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_PLAY');
											}

											$playLink = JRoute::_(PhocaDownloadRoute::getFileRoute($item->id,$item->catid,$item->alias, $item->catalias,0, 'play'));


											if ($iMime == 1) {
												$output .= '<div class="pd-filename phocadownloadfile phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'>';
											} else {
												$output .= '<div><div class="phocadownloadplay'.(int)$iSize.'">';
											}

											if ($playWindow == 1) {
												$output .= '<a  href="'.$playLink.'" onclick="'. $buttonPlOptions.'" >'. $textOutput.'</a>';
											} else {

												if ($display_bootstrap3_layout > 0) {
													// Bootstrap
													$output .= '<a class="pd-modal-button" data-toggle="modal" data-target="#phModalPlay" href="#" data-href="' . $playLink . '" ' . $buttonPlOptions . ' >'. $textOutput.'</a>';
													if (isset($bootstrapModal) && $bootstrapModal != '') {
														$output .= $bootstrapModal;
													}
												} else {
													$output .= '<a class="modal-button" href="'.$playLink.'" rel="'. $buttonPlOptions.'" >'. $textOutput.'</a>';
												}

											}
											$output .= '</div></div>';
										}
									} else {
										$output .= JText::_('PLG_CONTENT_PHOCADOWNLOAD_NO_FILE_FOR_PLAYING_FOUND');
									}




								} else if ($view == 'filepreviewlink') {


									if (isset($item->filename_preview) && $item->filename_preview != '') {
										$fileExt 	= PhocaDownloadFile::getExtension($item->filename_preview);
										if ($fileExt == 'pdf' || $fileExt == 'jpeg' || $fileExt == 'jpg' || $fileExt == 'png' || $fileExt == 'gif') {

											$filePath	= PhocaDownloadPath::getPathSet('filepreview');
											$filePath	= str_replace ( '../', JURI::base(true).'/', $filePath['orig_rel_ds']);
											$previewLink = $filePath . $item->filename_preview;
											//$previewWindow 	= $paramsC->get( 'preview_popup_window', 0 );

											// PREVIEW - - - - - - - - - - - -
											$windowWidthPr 	= (int)$previewwidth + 20;
											$windowHeightPr = (int)$previewheight + 20;
											if ($previewWindow == 1) {
												$buttonPr = new JObject();
												$buttonPr->set('methodname', 'js-button');
												$buttonPr->set('options', "window.open(this.href,'win2','width=".$windowWidthPr.",height=".$windowHeightPr.",scrollbars=yes,menubar=no,resizable=yes'); return false;");
											} else {
												Joomla\CMS\HTML\HTMLHelper::_('behavior.modal', 'a.modal-button');
												$document->addCustomTag( "<style type=\"text/css\"> \n"
											." #sbox-window.phocadownloadpreviewwindow   {background-color:#fff;padding:2px} \n"
											." #sbox-overlay.phocadownloadpreviewoverlay  {background-color:#000;} \n"
											." </style> \n");
												$buttonPr = new JObject();
												$buttonPr->set('name', 'image');
												$buttonPr->set('modal', true);
												$buttonPr->set('methodname', 'modal-button');
												$buttonPr->set('options', "{handler: 'iframe', size: {x: ".$windowWidthPr.", y: ".$windowHeightPr."}, overlayOpacity: 0.7, classWindow: 'phocadownloadpreviewwindow', classOverlay: 'phocadownloadpreviewoverlay'}");
												$buttonPr->set('optionsimg', "{handler: 'image', size: {x: 200, y: 150}, overlayOpacity: 0.7, classWindow: 'phocadownloadpreviewwindow', classOverlay: 'phocadownloadpreviewoverlay'}");
											}
											// - - - - - - - - - - - - - - -



											/*if ($text == '') {
												$text = JText::_('PLG_CONTENT_PHOCADOWNLOAD_PREVIEW');
											}*/

											if ($text !='') {
												$textOutput = $text;
											//} else if (isset($item->title) && $item->title != '') {
											//	$textOutput = $item->title;
											} else {
												$textOutput = JText::_('PLG_CONTENT_PHOCADOWNLOAD_PREVIEW');
											}
											if ($iMime == 1) {
												$output .= '<div class="pd-filename phocadownloadfile phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'>';
											} else {
												$output .= '<div><div class="phocadownloadpreview'.(int)$iSize.'">';
											}

											if ($previewWindow == 1) {
												$output .= '<a  href="'.$previewLink.'" onclick="'. $buttonPr->options.'" >'. $text.'</a>';
											} else {
												if ($fileExt == 'pdf') {
													// Iframe - modal
													$output	.= '<a class="modal-button" href="'.$previewLink.'" rel="'. $buttonPr->options.'" >'. $textOutput.'</a>';
												} else {
													// Image - modal
													$output	.= '<a class="modal-button" href="'.$previewLink.'" rel="'. $buttonPr->optionsimg.'" >'. $textOutput.'</a>';
												}
											}
											$output	.= '</div></div>';
										}
									} else {
										$output .= JText::_('PLG_CONTENT_PHOCADOWNLOAD_NO_FILE_FOR_PREVIEWING_FOUND');
									}

								} else {
									if ((isset($item->confirm_license) && $item->confirm_license > 0) || $fileView == 1) {
										$link = PhocaDownloadRoute::getFileRoute($item->id,$item->catid,$item->alias, $item->catalias,0, 'file');
										//'index.php?option=com_phocadownload&view=file&id='.$item->slug.'&Itemid='.$itemId

										if ($iMime == 1) {
											$output .= '<div class="pd-filename phocadownloadfile phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'><a href="'. JRoute::_($link).'" '. $targetOutput.'>'. $textOutput.'</a></div></div>';
										} else {
											$output .= '<div class="phocadownloadfile'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
										}

									} else {
										if ($item->link_external != '') {
											$link = $item->link_external;
										} else {
											$link = PhocaDownloadRoute::getFileRoute($item->id,$item->catid,$item->alias,$item->catalias,0, 'download');
										}
										//$link = PhocaDownloadRoute::getCategoryRoute($item->catid,$item->catalias,$item->sectionid);

										//'index.php?option=com_phocadownload&view=category&id='. $item->catslug. '&download='. $item->slug. '&Itemid=' . $itemId

										if ($iMime == 1) {
											$output .= '<div class="pd-filename phocadownloadfile phoca-dl-file-box-mod">'.  $imageFileName['filenamethumb']. '<div class="pd-document'.(int)$iSize.'" '. $imageFileName['filenamestyle'].'><a href="'. JRoute::_($link).'" '. $targetOutput.'>'. $textOutput.'</a></div></div>';
										} else {
											$output .= '<div class="phocadownloadfile'.(int)$iSize.'"><a href="'. JRoute::_($link).'" '.$targetOutput.'>'. $textOutput.'</a></div>';
										}
									}
								}
							}

						}
					break;

					// - - - - - - - - - - - - - - - -
					// YOUTUBE
					// - - - - - - - - - - - - - - - -
					case 'youtube':

						if ($url != '' && PhocaDownloadUtils::isURLAddress($url) ) {
							$l 			= new PhocaDownloadLayout();
							$pdVideo 	= $l->displayVideo($url, 0, $youtubewidth, $youtubeheight);
							$output		.= $pdVideo;
						} else {
							$output .= JText::_('PLG_CONTENT_PHOCADOWNLOAD_WRONG_YOUTUBE_URL');
						}
					break;


				}
				$article->text = preg_replace($regex_all, $output, $article->text, 1);
			}
		}// end if count_matches
		return true;
	}
}
?>
