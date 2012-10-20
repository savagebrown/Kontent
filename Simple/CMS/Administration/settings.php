<?php

	/////////////////////////////////////////////////////////////////////////
	# BEGIN Includes #
	/////////////////////////////////////////////////////////////////////////

	/* Error reporting and logging */
	require_once 'Functions/Error.php';

	/* Connection */
	require_once 'Connection.php';

	// Include access class before we go any further
	include 'Simple/CMS/Permissions/access.php';

	/* Functions */
	require_once 'Functions/Administration.php';
	require_once 'Functions/CMS.php';
	require_once 'Functions/Files.php';
	require_once 'Functions/Forms.php';
	require_once 'Functions/GreatEscape.php';
	require_once 'Functions/HTML.php';
	require_once 'Functions/Integers.php';
	require_once 'Functions/JavaScript.php';
	require_once 'Functions/Strings.php';
	require_once 'Functions/Inflector.php';
	require_once 'Functions/Wysiwyg.php';

	/* PEAR::XIPE */
	require_once 'HTML/Template/Xipe.php';

	/* PEAR::Quickform */
	require_once 'HTML/QuickForm.php';
	require_once 'Functions/QuickForm.php';

	/* Textile */
	require_once '3rdParty/Textile.php';

	$textile = new Textile();

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Paths #
	/////////////////////////////////////////////////////////////////////////

	$g['xipe']['path']		= 'Templates/'.$g['xipe']['admin'];
	$g['xipe']['options']	= array();
	// Template specific
	$g['page']['path']				= $g['global']['root_admin'].'/'.$g['xipe']['path'];
	$g['page']['images']			= $g['xipe']['path'].'/Images';
	$g['page']['buttons']			= $g['xipe']['path'].'/Buttons';
	$g['page']['css']				= $g['xipe']['path'].'/Stylesheets';
	// Universal
	$g['page']['icons']['social']	= $g['global']['assets'].'/Social';
	$g['page']['icons']['files']	= $g['global']['assets'].'/Files';
	$g['page']['js']				= $g['global']['assets'].'/Scripts';

	$g['page']['markup']		= '';
	$g['page']['js_top']		= '';
	$g['page']['js_bottom']		= '';
	$g['page']['header']		= '';
	$g['page']['instructions']	= '';
	$g['page']['footer']		= '';

	$g['mimes']['jpeg']			= array();

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Xipe Options #
	/////////////////////////////////////////////////////////////////////////

	// Set Xipe template options
	if ($g['global']['status']=='live'){
		$g['xipe']['options'] = array(
			'templateDir'  => 'Templates'
		);
	} else {
		$g['xipe']['options'] = array(
			'templateDir'  => 'Templates',
			'autoBraces'   => true,
			'forceCompile' => true,
			'filterLevel'  => 0,
			'logLevel'	   => 0,
			'enable-Cache' => false
		);
	}

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Javascripts #
	/////////////////////////////////////////////////////////////////////////

	$g['page']['js_top'] = <<<HTML

<!-- JQuery -->
<script type="text/javascript" src="{$g['page']['js']}/jQuery/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="{$g['page']['js']}/jQuery/jquery-ui/js/jquery-ui-1.8.14.custom.min.js"></script>

<script type="text/javascript" src="{$g['page']['js']}/DateRange/daterangepicker.jQuery.js"></script>

<!-- jHtmlArea -->
<script type="text/javascript" src="{$g['page']['js']}/jHtmlArea/jHtmlArea-0.7.0.js"></script>
<!-- Homebrew -->
<script type="text/javascript" src="{$g['page']['js']}/Homebrew/2ndChapter.js"></script>
<script type="text/javascript" src="{$g['page']['js']}/Homebrew/kms.js"></script>

HTML;

	$g['page']['js_bottom'] = <<<HTML

<!-- Bottom Javascript -- >

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] header #
	/////////////////////////////////////////////////////////////////////////

	$g['page']['header'] = <<<HTML

<link rel="stylesheet" rev="stylesheet" href="{$g['page']['css']}/default.css" media="screen" />
<link rel="Stylesheet" type="text/css" href="{$g['page']['js']}/jHtmlArea/css/jHtmlArea.css" />

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Copy Variables #
	/////////////////////////////////////////////////////////////////////////

	// Page Markup Reference ------------------------------------------------
	if ($g['xipe']['wysiwyg']==1) {
		// WYSIWYG accompanying sidebar
		$g['page']['markup'] = '';
	} else {
		// Textile accompanying sidebar
		$g['page']['markup'] = <<<HTML

<script type="text/javascript" charset="utf-8">
	$(document).ready(function(){
		$(".btn-viewexamples").click(function(){
		  $("#formatting_guide").slideToggle("fast");
		  $(this).toggleClass("active");
		});
	});
</script>

<div id="markup_ref">

	<h2>Formatting...</h2>
	<p><span class="highlight">Highlighted</span> fields accept simple,
	easy-to-write codes to format your text.
	<a class="btn-viewexamples" href="#">View some examples</a></p>
	<div id="formatting_guide" style="display:none;">
		<table>
			<tr>
				<th>For this&hellip;</th>
				<th>Type this&hellip;</th>
			</tr>
			<tr>
				<td><strong>Bold phrase</strong></td>
				<td>*Bold phrase*</td>
			</tr>
			<tr>
				<td><span style="font-style: italic;">Italic phrase</span></td>
				<td>_Italic phrase_</td>
			</tr>
			<tr>
				<td>
					<ul>
					<li>Item One</li>
					<li>Item Two</li>
					</ul>
				</td>
				<td>* Item One<br />* Item Two</td>
			</tr>
			<tr>
				<td>
					<ol>
						<li>Item One</li>
						<li>Item Two</li>
					</ol>
				</td>
				<td># Item One<br /># Item Two</td>
			</tr>
			<tr>
				<td><blockquote>Quote</blockquote></td>
				<td>bq. Quote here</td>
			</tr>

			<tr>
				<td><h1>Big</h1></td>
				<td>h1. Big</td>
			</tr>
			<tr>
				<td><h2>Normal</h2></td>
				<td>h2. Normal</td>
			</tr>
			<tr>
				<td colspan="2"><a href="http://google.com">Google</a></td>
			</tr>
			<tr>
				<td colspan="2">"Google":http://google.com</td>
			</tr>
			<tr>
				<td colspan="2"><a href="mailto:me@yahoo.com">Email Me</a></td>
			</tr>
			<tr>
				<td colspan="2">"Email Me":mailto:me@yahoo.com</td>
			</tr>
			<tr>
				<td colspan="2">
					Additional information and examples can be found at the
					<a href="http://www.textism.com/tools/textile/index.html" target="_blank">
						Textile
					</a> site.
				</td>
			</tr>

		</table>
	</div>

</div>

HTML;
	}

	// Page Footer ----------------------------------------------------------

	$cms_version = version_conversion();
	$g['page']['footer'] = <<<HTML

<p>Kontent Management System (KMS v$cms_version) &mdash; developed and maintained by <a href="{$g['administrator']['website']}">{$g['administrator']['name']}</a></p>

HTML;

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Arrays #
	/////////////////////////////////////////////////////////////////////////

	$g['mimes']['jpeg'] = array(
		'image/jpeg',
		'image/jpg',
		'image/jp_',
		'application/jpg',
		'application/jpeg',
		'application/x-jpg',
		'image/pjpeg',
		'image/pipeg',
		'image/vnd.swiftview-jpeg',
		'image/x-xbitmap');

	/////////////////////////////////////////////////////////////////////////
	# BEGIN [GLOBAL] Quickform Default Templates #
	/////////////////////////////////////////////////////////////////////////

	$qf_container = <<<HTML

	<div id="form_container">
		<form{attributes}>
			<table>
				{content}
			</table>
		</form>
	</div>

HTML;

	$qf_header = <<<HTML

	<h2>{header}</h2>

HTML;

	$qf_element = <<<HTML

	<!-- BEGIN error -->
	<tr>
	<td colspan="2">
	<div class="error">{error}</div>
	</td>
	</tr>
	<!-- END error -->

	<tr>
		<td class="label" valign="top">
		<span <!-- BEGIN required -->class="required"<!-- END required --> >
		{label}
		</span>
		</td>
		<td valign="top">{element}</td>
	</tr>

HTML;

	$qf_button = <<<HTML

	<tr>
		<td class="last"><span style="font-size:10px;" class="required">Required fields in bold</span></td>
		<td class="last">{element}</td>
	</tr>

HTML;

	$qf_plain = <<<HTML

	<!-- BEGIN error --><div class="error_full">{error}</div><!-- END error -->
	{element}

HTML;

?>