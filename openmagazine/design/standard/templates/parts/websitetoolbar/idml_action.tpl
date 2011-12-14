{def $has_ezidml = false()}
{if and( is_set( $current_node ), ezini( 'ExportClassSettings', 'MagazineSectionClassIdentifier', 'openmagazine.ini' )|contains( $current_node.class_identifier ) ) }
    {foreach $current_node.data_map as $attribute}
        {if $attribute.data_type_string|eq( 'ezidml' )}
        {set $has_ezidml = $attribute.content}        
        {/if}
    {/foreach}
{/if}

{if $has_ezidml}
    {if is_set( $has_ezidml.import_ez_contents ) }
        <a href="#contents" title="{'Jump to contents'|i18n( 'extension/openmagazine' )}">
            <img class="ezwt-input-image" src={'openmagazine/ezwt-om-jump.png'|ezimage()} alt="{'Jump to contents'|i18n( 'extension/openmagazine' )}" />
        </a>
        {if eq( $current_node.node_id, $has_ezidml.source_node_id )}
                {def $contentParent = $current_node}
        {else}
            {def $contentParent = fetch( 'content', 'node', hash( 'node_id', $has_ezidml.source_node_id ) )}
        {/if}
        <a href={concat( "openmagazine/sort/", $contentParent.node_id, "/(RedirectURL)/", $current_node.url_alias )|ezurl()} title="{'Sort source contents priority'|i18n( 'extension/openmagazine' )}">
            <img class="ezwt-input-image" src={'openmagazine/ezwt-om-sort.png'|ezimage()} alt="{'Sort source contents priority'|i18n( 'extension/openmagazine' )}"/>
        </a>
        {if is_set( $has_ezidml.have_contents )}
        <a href={concat( "openmagazine/export_idml/", $current_node.node_id, "/idml/")|ezurl()} title="{'Export Idml'|i18n('extension/openmagazine')}">
            <img class="ezwt-input-image" src={'openmagazine/ezwt-om-idml.png'|ezimage()} alt="{'Export Idml'|i18n( 'extension/openmagazine' )}" />
        </a>
            {if cond( ezini( 'ExportImagesSettings', 'DownloadZip', 'openmagazine.ini' )|eq( 'enabled' ) ) }
            <a href={concat( "openmagazine/export_idml/", $current_node.node_id, "/images/")|ezurl()} title="{'Export images zip'|i18n('extension/openmagazine')}">
                 <img class="ezwt-input-image" src={'openmagazine/ezwt-om-img.png'|ezimage()} alt="{'Export images zip'|i18n( 'extension/openmagazine' )}" />
            </a>
            {/if}
            {if ezini( 'DebugSettings', 'DebugOutput', 'site.ini' )|eq('enabled')}
            <a href={concat( "openmagazine/export_idml/", $current_node.node_id, "/_debug/")|ezurl()} title="{'Export debug'|i18n('extension/openmagazine')}">
                <img class="ezwt-input-image" src={'openmagazine/ezwt-om-debug.png'|ezimage()} alt="{'Export debug'|i18n( 'extension/openmagazine' )}" />
            </a>
            {/if}
        {/if}
    {/if}
        {if ezini( 'DebugSettings', 'DebugOutput', 'site.ini' )|eq('enabled')}
        <a href={concat( "openmagazine/export_idml/", $current_node.node_id, "/_simpledebug/")|ezurl()} title="{'IDML debug'|i18n('extension/openmagazine')}">
            <img class="ezwt-input-image" src={'openmagazine/ezwt-om-idmldebug.png'|ezimage()} alt="{'IDML debug'|i18n( 'extension/openmagazine' )}" />
        </a>
        {/if}
{/if}