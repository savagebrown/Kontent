<?php

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Instantiate Form #
    /////////////////////////////////////////////////////////////////////////

    $search_form = new HTML_QuickForm('frm', 'post');

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Process Search Form #
    /////////////////////////////////////////////////////////////////////////

    if ($search_form->getSubmitValue('item_search')) {
        $display_search_term = $search_form->getSubmitValue('item_search');
        $search_term = safe_escape('%'.$search_form->getSubmitValue('item_search').'%', 'str', true);

        $search_table_fields = (is_array($search_table_fields))?$search_table_fields:$default_table_fields;

        $sql_search = " AND \n( ";

        foreach( $search_table_fields as $term ) {
            $sql_search .= $term." LIKE ".$search_term.' OR ';
        }

        $sql_search = trim($sql_search);
        $sql_search = trim(substr($sql_search, 0, (strlen($sql_search)-2)));
    	$sql_search .= " )\n";

        $search_performed = true;
    } else {
        $search_performed = false;
    }

    /////////////////////////////////////////////////////////////////////////
    # BEGIN Build Form #
    /////////////////////////////////////////////////////////////////////////

    $search_renderer =& $search_form->defaultRenderer();
    $search_renderer->clearAllTemplates();
    $search_renderer->setFormTemplate('

<div id="search_form_container">
	<form{attributes}>
			{content}
	</form>
</div>

        ');
    $search_renderer->setHeaderTemplate('<h2>{header}</h2>');
    $search_renderer->setElementTemplate('

<!-- BEGIN error --><div class="error_full">{error}</div><!-- END error -->
<p>{label}</p>
<table id="search_field">
<tr>
    <td width="100%" id="search_input">{element}</td>

        ');

    $input_button = <<<HTML

    <td id="search_button">{element}</td>
</tr>
</table>

HTML;

    $search_renderer->setElementTemplate($input_button, 'btnSearch');

    // Autocompletion -------------------------------------------------------
    if ($search_form_autocompletion){
        $search_input =& $search_form->addElement('autocomplete', 'item_search', $search_form_intro);
        foreach( $search_table_fields as $term ) {
            $autocomplete_search .= ', '.$term;
        }
        $sql_muni = 'SELECT id'.$autocomplete_search.' FROM '.$search_table;
        $q_muni = $db->query($sql_muni);
        if (DB::isError($q_muni)) { sb_error($q_muni); }
        
        $autocomplete_options = array();
        while ($r_muni = $q_muni->fetchrow(DB_FETCHMODE_ASSOC)) {
            foreach( $search_table_fields as $v ) {
                $autocomplete_options[] = $r_muni[$v];
            }
        }
        $search_input->setOptions(array_unique($autocomplete_options));
    } else {
        $search_form->addElement('text', 'item_search', $search_form_intro);
    }
    // Rules ----------------------------------------------------------------
    $search_form->addRule('item_search', 'I need something to search. Help Me... Help You!', 'required', null, 'client');
    $search_form->addRule('item_search', 'Please enter at least 3 characters to search with', 'minlength', 3, 'client');
    // Button ---------------------------------------------------------------
    $search_form->addElement('image', 'btnSearch', $g['page']['buttons'].'/btn-search.png', array("value"=>"Search"));
    $search_form->addElement('hidden', 'category_id', $search_filter_category);
    // Add form to variable -------------------------------------------------
    $display_search_form = $search_form->toHtml();

?>