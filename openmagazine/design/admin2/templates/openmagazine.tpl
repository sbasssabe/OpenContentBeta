{def $has_ezidml = false()}
{if and( is_set( $node ), ezini( 'ExportClassSettings', 'MagazineSectionClassIdentifier', 'openmagazine.ini' )|contains( $node.class_identifier ) ) }
    {foreach $node.data_map as $_attribute}
        {if $_attribute.data_type_string|eq( 'ezidml' )}
        {set $has_ezidml = $_attribute}
        {/if}
    {/foreach}
{/if}

{if and( $has_ezidml, $has_ezidml.has_content )}
    {def $attribute_content = $has_ezidml.content}

    {ezcss_require( array( 'ezidml.css' ) )}
        
    
    {if is_set( $attribute_content.have_contents )}
    
        {def $errorTree = array()
             $errorBranch = array()
             $checkChar = false}
        
        {set-block variable=$contentTree}
            {def $contentNode = false()}
                        
            {foreach $attribute_content.content_tree as $priority => $contents}
            {set $contentNode = false()}

                {foreach $contents as $content}
                    {if is_set( $content.eZContentObjectTreeNodeID )}
                        {set $contentNode = fetch( 'content', 'node', hash( 'node_id', $content.eZContentObjectTreeNodeID ) ) }
                        {break}
                    {/if}
                {/foreach}
                            
{set-block variable=$contentBranch}
{set $errorBranch = array()}
    <tr>
        <td><strong>{'Tag'|i18n( 'extension/openmagazine' )}</strong></td>
        <td><strong>{'Attribute Identifier'|i18n( 'extension/openmagazine' )}</strong></td>
        <td><strong>{'Original Length'|i18n( 'extension/openmagazine' )}</strong></td>
        <td><strong>{'New Content Length'|i18n( 'extension/openmagazine' )}</strong></td>
        <td class="tight">&nbsp;</td>
    </tr>
    {foreach $contents as $content sequence array( 'bgdark','bglight' ) as $style}
        {if is_set($content.eZContentObjectAttributeID)}
            {set $checkChar = false}
            <tr class="{$style}">
                <td>
                    {$content.tag}
                </td>
                <td>
                    {$content.attribute_identifier}
                </td>
                {if and( is_set($content.type), $content.type|eq('image') ) }                                    
                    {if is_set($content.href)}
                        <td colspan="3" align="center">
                            <img border="1" src={$child.href|ezroot()} width="50" height="50" />
                        </td>
                    {else}
                        <td colspan="2" align="center">&nbsp;</td>
                        <td>
                            <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                            {set $errorBranch = $errorBranch|append( $content.tag )}
                        </td>
                    {/if}
                {else}
                    <td>
                        {if is_set($content.original_char_length)}{$content.original_char_length}{/if}
                    </td>
                    {if is_set($content.char_length)}
                        <td>
                            {$content.char_length}
                        </td>
                        <td>
                            {set $checkChar = check_char_length( $content.original_char_length, $content.char_length, $content.attribute_identifier )}
                            {if $checkChar}
                                <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                {set $errorBranch = $errorBranch|append( $checkChar )}
                            {/if}
                        </td>
                    {else}
                        <td>&nbsp;</td>
                        <td>
                            <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                            {set $errorBranch = $errorBranch|append( $content.tag )}
                        </td>                        
                    {/if}                
                {/if}
            </tr>
            {if is_set( $content.children )}
                {foreach $content.children as $child}
                {set $checkChar = false}
                    {if is_set( $child.xmltag )}
                        <tr class="{$style}">
                            <td>
                                {$content.tag}/{$child.tag}
                            </td>
                            <td>
                                {$content.attribute_identifier}/{$child.xmltag}-{$child.xmltag_priority}
                            </td>
                            {if and( is_set($child.type), $child.type|eq('image') ) }
                                {if is_set($child.href)}
                                    <td colspan="3" align="center">
                                        <img border="1" src={$child.href|ezroot()} width="50" height="50" />
                                    </td>
                                {else}
                                    <td colspan="2" align="center">&nbsp;</td>
                                    <td>    
                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                    </td>
                                {/if}
                            
                                </td>
                            {else}
                                <td>
                                    {if is_set($child.original_char_length)}{$child.original_char_length}{/if}
                                </td>
                                {if is_set($child.char_length)}
                                    <td>
                                        {$child.char_length}
                                    </td>
                                    <td>
                                        {set $checkChar = check_char_length( $child.original_char_length, $child.char_length, $content.attribute_identifier, $child.xmltag )}
                                        {if $checkChar}
                                            <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                            {set $errorBranch = $errorBranch|append( $checkChar )}
                                        {/if}
                                    </td>
                                {else}
                                    <td>&nbsp;</td>
                                    <td>
                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                    </td>
                                {/if}
                            {/if}
                        </tr>
                    {* Handle nested tagging error *}
                    {elseif is_set( $child.priority )}
                        <tr class="{$style}">
                            <td>
                                <span style="text-decoration:line-through;">{$content.tag}/</span>{$child.tag} (Tagging Error!)
                            </td>
                            <td>
                                <span style="text-decoration:line-through;">{$content.attribute_identifier}/</span>{$child.attribute_identifier}
                            </td>
                            {if and( is_set($child.type), $child.type|eq('image') ) }
                                {if is_set($child.href)}
                                    <td colspan="3" align="center">
                                        <img border="1" src={$child.href|ezroot()} width="50" height="50" />
                                    </td>
                                {else}
                                    <td colspan="2" align="center">&nbsp;</td>
                                    <td>
                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                    </td>
                                {/if}
                            {else}
                                <td>
                                    {if is_set($child.original_char_length)}{$child.original_char_length}{/if}
                                </td>
                                {if is_set($child.char_length)}
                                    <td>
                                        {$child.char_length}
                                    </td>
                                    <td>
                                        {set $checkChar = check_char_length( $child.original_char_length, $child.char_length, $child.attribute_identifier )}
                                        {if $checkChar}
                                            <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                            {set $errorBranch = $errorBranch|append( $checkChar )}
                                        {/if}   
                                    </td>
                                {else}
                                    <td>&nbsp;</td>
                                    <td>
                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                    </td>
                                {/if}
                            {/if}
                        </tr>
                    {/if}           
                {/foreach}
            {/if}
        {else}
            <tr class="{$style}">
                <td>{$content.tag}</td>
                <td>(unmatched)</td>
                <td>{if is_set($content.original_char_length)}{$content.original_char_length}{/if}</td>
                <td>&nbsp;</td>
                <td><span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span></td>
                {set $errorBranch = $errorBranch|append( concat( $content.tag ) )}
            </tr>
        {/if}
    {/foreach}
{/set-block}
            
            <table cellspacing="0" class="list">                
                <th colspan="4">         
                    {if $contentNode}
                    <span class="matched">{$priority}. <a href={$contentNode.url_alias|ezurl()} title="attribute#{$child.eZContentObjectAttributeID}">{$contentNode.name|wash()}</a></span>
                    {else}
                    <span class="unmatched">{'(unmatched)'|i18n( 'extension/openmagazine' )}</span>
                    {/if}
                </th>                
                <th class="tight">
                    {if $errorBranch|count()|gt( 0 )}
                        {set $errorTree = $errorTree|merge( $errorBranch )}
                        <span class="error"><strong>{$errorBranch|count()}</strong></span>
                    {else}
                        <span class="ok"></span>
                    {/if}
                    
                </th></tr>                
                {$contentBranch}
            </table>
            <br />
            
            {/foreach}
        {/set-block}
    {/if}
    
    {if is_set( $attribute_content.import_ez_contents ) }
        {if eq( $node.node_id, $attribute_content.source_node_id )}
            {def $contentParent = $node}
        {else}
            {def $contentParent = fetch( 'content', 'node', hash( 'node_id', $attribute_content.source_node_id ) )}
        {/if}
        
        {def $imageszip = cond( ezini( 'ExportImagesSettings', 'DownloadZip', 'openmagazine.ini' )|eq( 'enabled' ) )}

        <form class="idml-action" enctype="multipart/form-data" method="post" action={"/openmagazine/export_idml"|ezurl}>
            <fieldset>
            <input name="ExportAction" type="hidden" value="idml" />
            <input name="NodeID" type="hidden" value="{$has_ezidml.object.main_node_id}" />
            <a class="button" href={concat( "openmagazine/sort/", $contentParent.node_id, "/(RedirectURL)/", $node.url_alias )|ezurl()} title="{'Sort source contents priority'|i18n( 'extension/openmagazine' )}">{'Sort source contents priority'|i18n( 'extension/openmagazine' )}</a>
            {if is_set( $attribute_content.have_contents )}
            <input class="defaultbutton" type="submit" name="ExportIdml" value="{'Export Idml'|i18n('extension/openmagazine')}" />
                {if $imageszip}
                    <input class="button" type="submit" name="ExportImages" value="{'Export images zip'|i18n('extension/openmagazine')}" />
                {/if}
                <input class="button" type="submit" name="ExportDebug" value="{'Debug'|i18n('extension/openmagazine')}" />
            {/if}
            </fieldset>
        </form>
    {/if}      

    
    {if is_set( $contentParent )}
        
        {'Content imported from'|i18n( 'extension/openmagazine' )} <a href={$contentParent.url_alias|ezurl()} title="node#{$contentParent.node_id}">{$contentParent.name|wash()}</a>        
    
        {if is_set( $attribute_content.import_ez_contents ) }
            {if is_set( $attribute_content.have_contents )|not()}
            
                {if and( is_set( $contentParent ), $contentParent.children )}
                <div class="warning">
                    <p>{'Not found any content that matches the priorities of Idml layout'|i18n( 'extension/openmagazine' )}.
                    <a href={concat( "openmagazine/sort/", $contentParent.node_id, "/(RedirectURL)/", $node.url_alias )|ezurl()} title="{'Sort source contents priority'|i18n( 'extension/openmagazine' )}">{'Sort source contents priority'|i18n( 'extension/openmagazine' )}</a></p>
                </div>
                {/if}
                
            {else}
            
                <div class="object-right">
                {if $errorTree|count()|gt( 0 )}                
                    <span class="error"><strong>{"%number errors found."|i18n('extension/openmagazine', '', hash( '%number', $errorTree|count() ) )}</strong></span>
                {else}
                    <span class="ok"><strong>{'No errors found'|i18n('extension/openmagazine')}</strong></span>
                {/if}
                </div>
            
                {$contentTree}
            
            {/if}
        {/if}    

    {else}
        <p>
            {'This "Magazine Section" is empty: if you want to associate some content, please edit it, check "Insert eZ Publish content from ..." and publish some object as a child of this section; if you want to import the contents (texts and images) directly from the IDML file onto eZ Publish, when you import the file please check also "Create or update eZ Publish contents from IDML as children of this node". For further information, we recommend to read the documentation.'|i18n('extension/openmagazine')}
        </p>

    {/if}
    
{/if}