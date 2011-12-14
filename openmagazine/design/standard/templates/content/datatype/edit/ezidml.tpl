{default attribute_base='ContentObjectAttribute'}

{ezscript_require( array( 'ezjsc::jquery', 'ezjsc::jqueryio', 'jquery.svg.js', 'jquery.svgdom.min.js', 'jquery.ui.core.min.js', 'jquery.ui.widget.min.js', 'jquery.ui.tabs.min.js', 'jquery.jcarousel.js', 'jquery.tools.scrollable.min.js', 'ezidml.js' ) )}
{ezcss_require( array( 'ezidml.css' ) )}

<script type="text/javascript">
var svgElements = [];
{if is_set($attribute.content.svg_files)}
{def $split_source = false()}
{foreach $attribute.content.svg_files as $id => $source}
svgElements['{$id}'] ='{concat('openmagazine/svg/', $id, '::', $attribute.id, '::', $attribute.version)|ezurl(no)}';

{/foreach}
{/if}
var attributeID = "{$attribute.id}";
var repositorySearch = "{'Search by name'|i18n( 'extension/openmagazine' )}";
</script>
    

{def $sourceNode = false()
     $use_repository = cond( ezini( 'Repository', 'UseRepository', 'idml.ini' )|eq( 'enabled' ) )
     $repository_count = fetch( 'content', 'tree_count', hash(
                                                    'parent_node_id', ezini( 'Repository', 'ParentNode', 'idml.ini' ),
                                                    'class_filter_type', 'include',
                                                    'class_filter_array', ezini( 'ClassSettings', 'Section', 'idml.ini' ),
                                                    ) ) }

{if and( $use_repository, $repository_count ) }
<div class="tabs">
    <ul>
        <li><a href="#select">{'Add layout from repository'|i18n( 'extension/openmagazine' )}</a></li>
        <li><a href="#add">{'Add layout from IDML file'|i18n( 'extension/openmagazine' )}</a></li>
    </ul>
    <div id="select">
        <div class="repository-tools float-break">
            <div class="repository-helper">
                <span class="spinner"></span>
            </div>
            <div class="repository-search">
                <label for="repositorySearch" class="ui-helper-hidden">{'Search by name'|i18n( 'extension/openmagazine' )}</label>
                <input type="text" name="repositorySearch" id="repositorySearch" value="{'Search by name'|i18n( 'extension/openmagazine' )}" />
                <input id="search_repository" class="button" name="CustomActionButton[{$attribute.id}_search_repository]" type="submit" value="{'Search'|i18n( 'extension/openmagazine' )}" />    
                <input id="cancel_search_repository" class="button" name="CustomActionButton[{$attribute.id}_search_repository]" type="reset" value="{'Reset search'|i18n( 'extension/openmagazine' )}" />    
            </div>
        </div>
        
        <div id="repository-carousel-container" class="repository-carousel-container">
            <ul id="repository-carousel" class="repository-carousel"></ul>   
        </div>
    </div>
{/if}
    
    <div id="add" class="ui-tabs-panel">    

        <input style="float:right" type="image" title="Preview" name="CustomActionButton[{$attribute.id}_add_idml_file]" src="/extension/ezwt/design/standard/images/websitetoolbar/ezwt-icon-preview.png" />

        <label for="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_file">{'New IDML file for upload'|i18n( 'extension/openmagazine' )}: </label>
        <input id="ezcoa-{if ne( $attribute_base, 'ContentObjectAttribute' )}{$attribute_base}-{/if}{$attribute.contentclassattribute_id}_{$attribute.contentclass_attribute_identifier}_file" class="ezcc-{$attribute.object.content_class.identifier} ezcca-{$attribute.object.content_class.identifier}_{$attribute.contentclass_attribute_identifier}" name="{$attribute_base}_data_idmlname_{$attribute.id}" type="file" />
       
        <p>
            <input id="create_or_update_ez_contents" name="{$attribute_base}_create_or_update_ez_contents_{$attribute.id}" type="checkbox" value="1" />{'Create or update eZ Publish contents from IDML as children of this node'|i18n( 'extension/openmagazine' )}
        </p>

        
    </div>
    
{if and( $use_repository, $repository_count ) }
</div> {*close tab div *}
{/if}

<div class="block">
    <label for="import_ez_contents">
        <input id="import_ez_contents" class="button" name="{$attribute_base}_import_ez_contents_{$attribute.id}" type="checkbox" value="1" {if is_set($attribute.content.import_ez_contents)}checked="checked"{/if} />
        {'Insert eZ Publish content from'|i18n( 'extension/openmagazine' )}
    
        {if is_set( $attribute.content.source_node_id )}
            {set $sourceNode = fetch( 'content','node', hash( 'node_id', $attribute.content.source_node_id ) )}
            <strong><a href={$sourceNode.url_alias|ezurl()}>{$sourceNode.name|wash()}</a></strong>
        {elseif $attribute.object.main_node_id}
            {set $sourceNode = fetch( 'content','node', hash( 'node_id', $attribute.object.main_node_id ) )}
            <strong><a href={$sourceNode.url_alias|ezurl()}>{$sourceNode.name|wash()}</a></strong>
        {else}
            <strong><em>{'this node (when published) or'|i18n( 'extension/openmagazine' )}</em></strong>
        {/if}
            
        <input id="add_source_node_browse" class="button" name="CustomActionButton[{$attribute.id}_add_source_node_browse]" type="submit" value="{'Choose source'|i18n( 'extension/openmagazine' )}" />
    </label>    
</div>

{if $attribute.has_content}

    {def $attribute_content = $attribute.content}   
    
    {if is_set( $attribute_content.idml_info ) }
        <p class="idml-info text-center float-break">
        <small>
        <strong>{'Layout info:'|i18n( 'extension/openmagazine' )}</strong>
        {foreach $attribute_content.idml_info as $key => $value}
            <strong>{$key|i18n( 'extension/openmagazine' )}:</strong>
            {if $value|is_array() }
                {$value|implode(', ')}
            {else}
                {$value}
            {/if}
            {delimiter} - {/delimiter}
        {/foreach}
        </small>
        </p>
    {/if}

    {foreach $attribute_content.svg_files as $id => $source}
        <div id="spread-container-{$id}" class="spread-container" style="width:{$attribute_content.spreads.$id.width}px; height:{$attribute_content.spreads.$id.height}px;"></div>
    {/foreach} 

{/if}

