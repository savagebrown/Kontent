<?php
	require_once 'Includes/Configuration.php';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Display Messages #
	/////////////////////////////////////////////////////////////////////////

	$display_message = '';

	// Updated message
	if ($_GET['updated'] == true) {
		$display_message = '<div onclick="new Effect.Fade(this);" id="display_message" class="success_full">
							Your website settings have been updated successfully.</div>';
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Page Settings #
	/////////////////////////////////////////////////////////////////////////

	$page_vars			  = build_page($db, 11);
	$display_page_title	  = $page_vars['title'];
	$display_mainmenu	  = $page_vars['mainmenu'];
	$display_utility_menu = $page_vars['utilitymenu'];
	$display_submenu	  = str_replace("List", "Public", $page_vars['submenu']);
	$g['page']['instructions']  = '';//$textile->textileThis($page_vars['instructions']);
	$g['page']['markup']  = ($page_vars['markupref']) ? $g['page']['markup'] : '';

	$body = "public_settings";
	
	$display_content_title = 'Website settings';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Default Variables #
	/////////////////////////////////////////////////////////////////////////

	// Get content values for default
	$sql = <<<SQL

SELECT
	meta_description,
	meta_keywords,

	sidebar,
	footer,

	contact_thankyou,
	contact_email,

	facebook,
	twitter,
	linkedin,
	googleplus,
	youtube,
	vimeo,
	flickr
FROM
	settings_public
WHERE
	id = 666

SQL;
	$q = $db->query($sql);
	if (DB::iserror($q)) { sb_error($q); }

	$r = $q->fetchrow(DB_FETCHMODE_ASSOC);
	if (DB::iserror($q)) { sb_error($q); }


	// Match field names with row values and instructions for each
	$update_vars['meta_keywords']	 = $r['meta_keywords'];
	$update_inst['meta_keywords']	 = '<em>(These terms and phrases are used 
										by search engines to determine
										relevancy of performed searches.
										Seperate terms and phrases with commas.)</em>';
	$update_vars['meta_description'] = $r['meta_description'];
	$update_inst['meta_description'] = '<em>(This copy is displayed to 
										introduce your site in a
										listing of search results.)</em>';
	$update_vars['contact_thankyou'] = $r['contact_thankyou'];
	$update_inst['contact_thankyou'] = '<em>(This is the message text displayed
										after someone has submitted the contact
										form.)</em>';

	if ($settings['announcement']['active']) {
		$update_vars['sidebar'] = $r['sidebar'];
		$update_inst['sidebar'] = ($settings['announcement']['instructions'])?$settings['announcement']['instructions']:'<em>(This field is used to displayed an announcement on your homepage)</em>';
		$settings['announcement']['label'] = ($settings['announcement']['label'])?$settings['announcement']['label']:'Announcement:';
		$settings['announcement']['rows'] = 3;
	}

	// TODO: Make settings more flexible. Increase universal options.
	// Footer
	$update_vars['footer'] = $r['footer'];
	$update_inst['footer'] = ($settings['footer']['instructions'])?$settings['footer']['instructions']:'<em>(This copy is displayed at the bottom of your website pages)</em>';
	$settings['footer']['label'] = ($settings['footer']['label'])?$settings['footer']['label']:'Footer Copy:';


	$update_vars['contact_email']	 = $r['contact_email'];
	$update_inst['contact_email']	 = '<em>(Contact form notifications will be 
										sent to email addresses set here. 
										Separate multiple addresses with 
										commas.)</em>';

	$update_vars['facebook']		= ($r['facebook'])?$r['facebook']:'http://';
	$update_vars['twitter']			= ($r['twitter'])?$r['twitter']:'http://';
	$update_vars['linkedin']		= ($r['linkedin'])?$r['linkedin']:'http://';
	$update_vars['flickr']			= ($r['flickr'])?$r['flickr']:'http://';
	$update_vars['googleplus']		= ($r['googleplus'])?$r['googleplus']:'http://';
	$update_vars['youtube']			= ($r['youtube'])?$r['youtube']:'http://';
	$update_vars['vimeo']			= ($r['vimeo'])?$r['vimeo']:'http://';

	/////////////////////////////////////////////////////////////////////////
	# BEGIN QuickForm #
	/////////////////////////////////////////////////////////////////////////

	// Instantiate QuickForm
	$form = new HTML_QuickForm('frm', 'post');
	// Instantiate the renderer
	$renderer =& $form->defaultRenderer();
	// Clear QuickForm template
	$renderer->clearAllTemplates();
	// Define new templates
	$renderer->setFormTemplate('<form{attributes}>{content}</form>');
	$renderer->setHeaderTemplate('<tr><td colspan="2"><h3>{header}</h3></td></tr>');
	$renderer->setFormTemplate('
		<div id="form_container">
			<form{attributes}>
				<table cellspacing="0" cellpadding="0">
					{content}
				</table>
			</form>
		</div>
		');

	$renderer->setElementTemplate('

<!-- BEGIN error --><tr><td colspan="2" class="error">{error}</td></tr><!-- END error -->

<tr>
	<td class="label"><span <!-- BEGIN required -->class="required"<!-- END required --> >{label}</span></td>
	<td>{element}</td>
</tr>

		');

	$btn_alt_html = <<<HTML

<tr>
	<td class="last"><span style="font-size:10px;"><span class="required">All fields are required</span></td>
	<td class="last">{element}</td>
</tr>

HTML;

	$renderer->setElementTemplate($btn_alt_html, 'btnUpdate');

	// Contact Form Settings --------------------------------------------------
	$form->addElement('header', 'hr', 'Contact Form Settings');

	if ($g['xipe']['wysiwyg']) { 
		$thankyou_attrs = array('id'=>'thankyou', "rows"=>"5");
		$form->addElement('html', textarea_wysiwyg('thankyou'));
	} else {
		$thankyou_attrs = $attrs;
	}
	$form->addElement('textarea', 'contact_thankyou', '<span class="highlight">Contact Thankyou Copy:</span><br />'.$update_inst['contact_thankyou'], $thankyou_attrs);
	$form->addRule('contact_thankyou', 'The information you submit here is what is displayed to the user upon successful completion of the contact form. Let them know that they have done well:)', 'required');

	// Contact Emails ---------------------------------------------------------
	$form->addElement('textarea', 'contact_email', 'Contact Email:<br />'.$update_inst['contact_email'], $attrs);
	$form->addRule('contact_email', 'Please enter at least one valid email address.', 'required', null, 'client');

	// BUTTON -----------------------------------------------------------------

	// Update info button
	$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-savechanges.gif', 'value=Update');

	$form->addElement('header', 'hr', 'Universal Copy');

	// Announcement -----------------------------------------------------------
	if ($settings['announcement']['active']) {
		$form->addElement('textarea', 'sidebar', $settings['announcement']['label'].'<br />'.$update_inst['sidebar'], "rows=".$settings['announcement']['rows']);
	}

	// Footer Copy ------------------------------------------------------------
	if ($g['xipe']['wysiwyg']==1) { 
		$footercopy_attrs = array('id'=>'footercopy', "rows"=>"5");
		$form->addElement('html', textarea_wysiwyg('footercopy'));
	} else {
		$footercopy_attrs = $attrs;
	}
	$form->addElement('textarea', 'footer', '<span class="highlight">'.$settings['footer']['label'].'</span><br />'.$update_inst['footer'], $footercopy_attrs);
	$form->addRule('footer', 'Some footer copy is required.', 'required', null, 'client');

	// BUTTON -----------------------------------------------------------------

	// Update info button
	$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-savechanges.gif', 'value=Update');

	// Social Links -----------------------------------------------------------
	if ($social_facebook_display	||
		$social_twitter_display		||
		$social_linkedin_display	||
		$social_googleplus_display	||
		$social_youtube_display		||
		$social_vimeo_display		||
		$social_flikr_display) {

		$form->addElement('header', 'hr', 'Social Links');

		if ($social_facebook_display)	{ $form->addElement('text', 'facebook', 'Your Facebook Account:', array('class'=>'medium social facebook'));		}
		if ($social_twitter_display)	{ $form->addElement('text', 'twitter', 'Your Twitter Account:', array('class'=>'medium social twitter'));			}
		if ($social_linkedin_display)	{ $form->addElement('text', 'linkedin', 'Your LinkedIn Account:', array('class'=>'medium social linkedin'));		}
		if ($social_flickr_display)		{ $form->addElement('text', 'flickr', 'Your Flickr Account:', array('class'=>'medium social flickr'));				}
		if ($social_googleplus_display)	{ $form->addElement('text', 'googleplus', 'Your Google Plus Account:', array('class'=>'medium social googleplus'));	}
		if ($social_youtube_display)	{ $form->addElement('text', 'youtube', 'Your YouTube Channel:', array('class'=>'medium social youtube'));			}
		if ($social_vimeo_display)		{ $form->addElement('text', 'vimeo', 'Your Vimeo Channel:', array('class'=>'medium social vimeo'));					}
	}
	
	// BUTTON -----------------------------------------------------------------

	// Update info button
	$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-savechanges.gif', 'value=Update');

	// Meta Info --------------------------------------------------------------
	$form->addElement('header', 'hr', 'Metadata Settings');

	// Set attributes of textarea
	$attrs = array("rows"=>"8","cols"=>"50","alt"=>"Website Settings");
	// Meta Keywords
	$form->addElement('textarea', 'meta_keywords', 'Meta Keywords:<br />'.$update_inst['meta_keywords'], $attrs);
	$form->addRule('meta_keywords', 'Please add at least a few keywords.', 'required', null, 'client');

	$attrs = array("rows"=>"5");

	// Meta Description
	$form->addElement('textarea', 'meta_description', 'Meta Description:<br />'.$update_inst['meta_description'], $attrs);
	$form->addRule('meta_description', 'A description is required.', 'required', null, 'client');

	// BUTTON -----------------------------------------------------------------

	// Update info button
	$form->addElement('image', 'btnUpdate', $g['page']['buttons'].'/btn-savechanges.gif', 'value=Update');

	$form->addElement('hidden', 'formcheck', $title);

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Process Form #
	/////////////////////////////////////////////////////////////////////////

	if ($form->getSubmitValue('formcheck') == $title) {

	   if ($form->validate()) {

			// Build an array from the submitted form values
			$submit_vars = array(
				'meta_keywords'		=> safe_escape($form->getSubmitValue('meta_keywords'),		'str',true),
				'meta_description'	=> safe_escape($form->getSubmitValue('meta_description'),	'str',true),
				'contact_thankyou'	=> safe_escape($form->getSubmitValue('contact_thankyou'),	'wysiwyg', true),
				'footer'			=> safe_escape($form->getSubmitValue('footer'),				'wysiwyg', true),
				'sidebar'			=> safe_escape($form->getSubmitValue('sidebar'),			'str',true),
				'contact_email'		=> safe_escape($form->getSubmitValue('contact_email'),		'str',true),
				'facebook'			=> safe_escape($form->getSubmitValue('facebook'),			'str',true),
				'twitter'			=> safe_escape($form->getSubmitValue('twitter'),			'str',true),
				'linkedin'			=> safe_escape($form->getSubmitValue('linkedin'),			'str',true),
				'googleplus'		=> safe_escape($form->getSubmitValue('googleplus'),			'str',true),
				'youtube'			=> safe_escape($form->getSubmitValue('youtube'),			'str',true),
				'vimeo'				=> safe_escape($form->getSubmitValue('vimeo'),				'str',true),
				'flickr'			=> safe_escape($form->getSubmitValue('flickr'),				'str',true)
								);

				// Check if a form was submitted
				if ($form->getSubmitValue('btnUpdate_x')) {

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Update #
	/////////////////////////////////////////////////////////////////////////

					// update album information
					$sql_u = "UPDATE settings_public SET
						 
						meta_keywords		= ".$submit_vars['meta_keywords'].",
						meta_description	= ".$submit_vars['meta_description'].",
						contact_thankyou	= ".$submit_vars['contact_thankyou'].",
						footer				= ".$submit_vars['footer'].",
						sidebar				= ".$submit_vars['sidebar'].",
						contact_email		= ".$submit_vars['contact_email'].",
						facebook			= ".$submit_vars['facebook'].",
						twitter				= ".$submit_vars['twitter'].",
						linkedin			= ".$submit_vars['linkedin'].",
						googleplus			= ".$submit_vars['googleplus'].",
						youtube				= ".$submit_vars['youtube'].",
						vimeo				= ".$submit_vars['vimeo'].",
						flickr				= ".$submit_vars['flickr']."
							   WHERE
								 id =  666";

					$q_u = $db->query($sql_u);
					if (DB::iserror($q_u)) { sb_error($q_u); }

					// Log activity
					if ($g['log']['active']==1) {
						$log = new LogManager();
						$log->adminLogger($g['log']['file'], $user->get_fullname(), 'Updated website settings');
					}

					go_to(null,'?updated=true');
				}
		}

	}

	$form->setDefaults($update_vars);
	$display_form = $form->toHtml();

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Xipe #
	/////////////////////////////////////////////////////////////////////////

	$tpl = new HTML_Template_Xipe($g['xipe']['options']);
	$tpl->compile($g['xipe']['path'].'/default.tpl');
	include($tpl->getCompiledTemplate());
?>