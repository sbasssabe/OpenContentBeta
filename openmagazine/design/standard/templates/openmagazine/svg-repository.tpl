{def $idml_attribute_name = ezini( 'FileImport', 'DefaultImportIdmlAttribute', 'idml.ini' )
     $idml_attribute = false()
     $repository_parent = fetch( 'content', 'node', hash( 'node_id', ezini( 'Repository', 'ParentNode', 'idml.ini' ) ) )}
{if $search}
{def $repository_search = fetch( 'content', 'search', hash(
                                                    'text', $search,
                                                    'subtree_array', array( ezini( 'Repository', 'ParentNode', 'idml.ini' ) ),
                                                    'class_id', ezini( 'ClassSettings', 'Section', 'idml.ini' ),
                                                    'limit', $limit,
                                                    'offset', $offset
                                                    ) ) 
     $repository = $repository_search['SearchResult']
     $repository_count = $repository_search['SearchCount']}
{else}
{def $repository_count = fetch( 'content', 'tree_count', hash(
                                                    'parent_node_id', ezini( 'Repository', 'ParentNode', 'idml.ini' ),
                                                    'class_filter_type', 'include',
                                                    'class_filter_array', ezini( 'ClassSettings', 'Section', 'idml.ini' ),
                                                    ) ) 
     $repository = fetch( 'content', 'tree', hash(
                                            'parent_node_id', ezini( 'Repository', 'ParentNode', 'idml.ini' ),
                                            'class_filter_type', 'include',
                                            'class_filter_array', ezini( 'ClassSettings', 'Section', 'idml.ini' ),
                                            'limit', $limit,
                                            'offset', $offset,
                                            'sort_by', $repository_parent.sort_array
                                            ) ) }
{/if}
<span id="total" class="ui-helper-hidden">{$repository_count}</span>
{foreach $repository as $repo}
    <div id="repository-{$repo.node_id}" class="repository-container">
        
        {set $idml_attribute = $repo.data_map.$idml_attribute_name.content}

        <h3{if $search} style="margin:0;"{/if}>{$repo.name|wash} ({$item}/{$repository_count})</h3>
        {if $search}
        <small>{'Search for "%1" returned %2 matches'|i18n("design/ezwebin/content/search",,array($search|wash,$repository_count))}</small>
        {/if}
        
        <input id="add_from_{$repo.object.id}" class="add_form_repository_button button" type="submit" name="CustomActionButton[{$attribute_id}_add_from_repository-{$repo.object.id}]" value="{'Use this layout'|i18n( 'extension/ezmagazine' )}" />
        
        {if is_set( $idml_attribute.idml_info ) }                        
            <div class="repository-description">
            {foreach $idml_attribute.idml_info as $key => $value}
                <p>
                    <strong>{$key}:</strong>
                    {if $value|is_array() }
                        {$value|implode(', ')}
                    {else}
                        {$value}
                    {/if}
                </p>  
            {/foreach}
            </div>
        {/if}
        
        <div class="repository-svg-container float-break">
            <div class="items">
            {foreach $idml_attribute.spreads as $id => $spread}
                <div id="svgload-{$repo.node_id}-{$id}" class="repository-svg"></div>
            {/foreach}
            </div>  
        </div>
        
    </div>
{/foreach}