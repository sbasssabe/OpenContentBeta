{if $attribute.has_content}
    {def $attribute_content = $attribute.content}

    {ezscript_require( array( 'ezjsc::jquery', 'ezjsc::jqueryio', 'jquery.svgdom.js', 'jquery.svg.js' ) )}
    {ezcss_require( array( 'ezidml.css' ) )}
    
    {literal}
    <script type="text/javascript">

    var svgElements = [];
    {/literal}
    {def $split_source = false()}
    {foreach $attribute_content.svg_files as $id => $source}
    svgElements['{$id}'] ='{concat('openmagazine/svg/', $id, '::', $attribute.id, '::', $attribute.version)|ezurl(no)}';
    {/foreach}
    expand = "{'expand all'|i18n( 'extension/openmagazine' )}";
    collapse = "{'collapse all'|i18n( 'extension/openmagazine' )}";
    var nodeID = "{$#node.node_id}";
    {literal}
    
    $(function() {
        
        makeSvgInteractive = function(){
            var root = this;
            $( '.has_tag', this ).each( function(){
                
                var classes = (this.className ? this.className.baseVal : this.getAttribute('class')).split(/\s+/);
                for(var i = 0; i < classes.length; i++){
                    if ( classes[i].substring(6,0) == 'story_' ){
                        var contentID = classes[i].replace( 'story_', '' );
                    }
                }
                var postData = { nodeID: nodeID, contentID: contentID };
                var tooltip = $("<div />")
                    .attr( 'id', 'tooltip-'+contentID )
                    .addClass( 'tooltip-idml' )
                    .html( '<div class="repository-helper"><span class="spinner"></span></div>' )
                    .appendTo( 'body' )
                    .hide();
                
                $.ez( 'ezidmlfunctionsjs::fetchContent',
                    postData,
                    function(data){                        
                        if ( typeof data.content == 'string' && data.content !== null )
                            tooltip.html( data.content );
                        else
                            tooltip.html('<span class="error"><strong>{/literal}{'Not found!'|i18n( 'extension/openmagazine' )}{literal}</strong></span>');                            
                    }
                );

                
                $(this).bind({
                    mousemove: function(e) {
                        var css = {};
                        var top = e.pageY;
                        var left = e.pageX;	
                        bottom = $(window).height() - e.pageY;
                        right = $(window).width() - e.pagex;			
                        //if ( top > 500)
                        //    css = $.extend(css, {top: 'auto', bottom: bottom });
                        //else				
                            css = $.extend(css, {top: top + 5, bottom: 'auto'});
                        if ( left > 700)
                            css = $.extend(css, {left: left - 235});
                        else
                            css = $.extend(css, {left: left + 5});
                        tooltip.css(css).show();
                    },
                    mouseout: function(e) {
                        tooltip.hide();
                    }
                });
            });
            return this;
        }
        
        $('.spread-container').each(
            function(){
                var id = $(this).attr( 'id' ).replace("spread-container-", "");
                var source = svgElements[id];
                $(this).svg({ loadURL: source, onLoad: makeSvgInteractive });
            }
        );
        var toggledetail = function(){
            $(this).parent().next().toggle();
            if ($(this).parent().find('.toggledetail').html() == '[+]')
                $(this).parent().find('.toggledetail').html( '[-]' );
            else
                $(this).parent().find('.toggledetail').html( '[+]' );
        }
        $('ol.content_matched h3 span').bind( 'click', toggledetail );
        $('h2#contents small').bind( 'click', function(){
            if ($(this).html() == '['+expand+']' ){
                $(this).html( '['+collapse+']' );
                $('ol.content_matched table').show();
                $('ol.content_matched h3 span.toggledetail').html( '[-]' );
            }
            else{
                $(this).html( '['+expand+']' );
                $('ol.content_matched table').hide();
                $('ol.content_matched h3 span.toggledetail').html( '[+]' );
            }
            //$('ol.content_matched h3 span').each( toggledetail );
        });        
    });
    </script>
    {/literal}
    
    {if is_set( $attribute_content.have_contents )}
    
        {def $errorTree = array()
             $errorBranch = array()
             $checkChar = false}
        
        {set-block variable=$contentTree}
        <div class='float-break'>
        
            {def $contentNode = false()}
            
            <ol class="content_matched float-break">
            
            {foreach $attribute_content.content_tree as $priority => $contents}
            {set $contentNode = false()}
    
            <li class="float-break">
                {foreach $contents as $content}
                    {if is_set( $content.eZContentObjectTreeNodeID )}
                        {set $contentNode = fetch( 'content', 'node', hash( 'node_id', $content.eZContentObjectTreeNodeID ) ) }
                        {break}
                    {/if}
                {/foreach}
                            
                {set-block variable=$contentBranch}
                {set $errorBranch = array()}
                <table class='list detail' style="display:none;">
                    <tr>
                        <th>{'Tag'|i18n( 'extension/openmagazine' )}</th>
                        <th>{'Attribute Identifier'|i18n( 'extension/openmagazine' )}</th>
                        <th>{'Original Length'|i18n( 'extension/openmagazine' )}</th>
                        <th>{'New Content Length'|i18n( 'extension/openmagazine' )}</th> 
                    </tr>
                    
                    {foreach $contents as $content}
                        
                        {if is_set($content.eZContentObjectAttributeID)}
                            
                            {set $checkChar = false}
                            <tr>
                                <td>{$content.tag}</td>
                                <td><a href={$contentNode.url_alias|ezurl()} title="attribute#{$content.eZContentObjectAttributeID}">{$content.attribute_identifier}</a></td>
                                
                                {if and( is_set($content.type), $content.type|eq('image') ) }
                                
                                    <td colspan="2" align="center">
                                        {if is_set($content.href)}
                                            <img border="1" src={$content.href|ezroot()} width="50" height="50" />
                                        {else}
                                            <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                            {set $errorBranch = $errorBranch|append( $content.tag )}
                                        {/if}
                                    </td>
                                
                                {else}
                                
                                    <td>{if is_set($content.original_char_length)}{$content.original_char_length}{/if}</td>
                                    <td>
                                        {if is_set($content.char_length)}
                                            {$content.char_length}
                                            
                                            {set $checkChar = check_char_length( $content.original_char_length, $content.char_length, $content.attribute_identifier )}
                                            {if $checkChar}
                                                <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                                {set $errorBranch = $errorBranch|append( $checkChar )}
                                            {/if}
                                            
                                        {else}
                                            <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                            {set $errorBranch = $errorBranch|append( $content.tag )}
                                        {/if}
                                
                                    </td>
                                {/if}
                            
                            </tr>
                            
                            {if is_set( $content.children )}
                                
                                {foreach $content.children as $child}
                                
                                {set $checkChar = false}
                                
                                    {if is_set( $child.xmltag )}
                                        
                                        <tr>
                                            <td>{$content.tag}/{$child.tag}</td>
                                            <td><a href={$contentNode.url_alias|ezurl()} title="attribute#{$content.eZContentObjectAttributeID}">{$content.attribute_identifier}/{$child.xmltag}-{$child.xmltag_priority}</a></td>
                                            
                                            {if and( is_set($child.type), $child.type|eq('image') ) }
                                            
                                                <td colspan="2" align="center">
                                                    {if is_set($child.href)}
                                                        <img border="1" src={$child.href|ezroot()} width="50" height="50" />
                                                    {else}
                                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                                    {/if}
                                            
                                                </td>
                                            
                                            {else}
                                            
                                                <td>{if is_set($child.original_char_length)}{$child.original_char_length}{/if}</td>
                                                <td>
                                            
                                                    {if is_set($child.char_length)}
                                                        {$child.char_length}

                                                        {set $checkChar = check_char_length( $child.original_char_length, $child.char_length, $content.attribute_identifier, $child.xmltag )}
                                                        {if $checkChar}
                                                            <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                                            {set $errorBranch = $errorBranch|append( $checkChar )}
                                                        {/if}

                                                    {else}
                                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                                    {/if}
                                            
                                                </td>
                                            
                                            {/if}
                                        </tr>
                                    
                                    {* Handle nested tagging error *}
                                    {elseif is_set( $child.priority )}
                                       
                                        <tr>
                                            <td><span style="text-decoration:line-through;">{$content.tag}/</span>{$child.tag} (Tagging Error!)</td>
                                            <td><a href={$contentNode.url_alias|ezurl()} title="attribute#{$child.eZContentObjectAttributeID}"><span style="text-decoration:line-through;">{$content.attribute_identifier}/</span>{$child.attribute_identifier}</a></td>
                                            
                                            {if and( is_set($child.type), $child.type|eq('image') ) }
                                             
                                                <td colspan="2" align="center">
                                                    {if is_set($child.href)}
                                                        <img border="1" src={$child.href|ezroot()} width="50" height="50" />
                                                    {else}
                                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                                    {/if}
                                                </td>
                                            
                                            {else}
                                             
                                                <td>{if is_set($child.original_char_length)}{$child.original_char_length}{/if}</td>
                                                <td>
                                                    {if is_set($child.char_length)}
                                                        {$child.char_length}

                                                        {set $checkChar = check_char_length( $child.original_char_length, $child.char_length, $child.attribute_identifier )}
                                                        {if $checkChar}
                                                            <span class="error" title="{'Character length problem'|i18n( 'extension/openmagazine' )}"></span>
                                                            {set $errorBranch = $errorBranch|append( $checkChar )}
                                                        {/if}

                                                    {else}
                                                        <span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span>
                                                        {set $errorBranch = $errorBranch|append( concat( $content.tag, '/', $child.tag ) )}
                                                    {/if}
                                            
                                                </td>
                                        
                                            {/if}
                                        
                                        </tr>
                                   
                                    {/if}           
                                
                                {/foreach}
                            {/if}
                            
                        {else}
                            
                            <tr>
                                <td>{$content.tag}</td>
                                <td>(unmatched)</td>
                                <td>{if is_set($content.original_char_length)}{$content.original_char_length}{/if}</td>
                                <td><span class="error" title="{'Not found!'|i18n( 'extension/openmagazine' )}"></span></td>
                                {set $errorBranch = $errorBranch|append( concat( $content.tag ) )}
                            </tr>
                            
                        {/if}
                        
                    {/foreach}
                    
                </table>
                {/set-block}
                
                <h3 class="attribute-header ezidml-node">
                    
                    {if $contentNode.object.can_edit}
                        <form method="post" action={"content/action"|ezurl} class='edit-matched' style="display:inline">
                            <input type="hidden" name="ContentObjectLanguageCode" value="{ezini( 'RegionalSettings', 'ContentObjectLocale', 'site.ini')}" />
                            <input type="image" src={"websitetoolbar/ezwt-icon-edit.png"|ezimage} name="EditButton" title="{'Edit: %node_name [%class_name]'|i18n( 'design/standard/parts/website_toolbar', , hash( '%node_name', $contentNode.name|wash(), '%class_name', $contentNode.object.content_class.name|wash() ) )}" />
                            <input type="hidden" name="ContentObjectID" value="{$contentNode.object.id}" />
                            <input type="hidden" name="NodeID" value="{$contentNode.node_id}" />
                            <input type="hidden" name="ContentNodeID" value="{$contentNode.node_id}" />
                        </form>
                    {else}
                        <span class='toggledetail'>[+]</span>
                    {/if}
                    
                    {if $contentNode}
                    <span class="matched">
                        {$contentNode.name|wash()}
                    </span>
                    
                    {else}
                    <span class="unmatched">{'(unmatched)'|i18n( 'extension/openmagazine' )}</span>
                    {/if}
                    
                    {if $errorBranch|count()|gt( 0 )}
                        {set $errorTree = $errorTree|merge( $errorBranch )}
                        <span class="error"><strong>{$errorBranch|count()}</strong></span>
                    {else}
                        <span class="ok"></span>
                    {/if}
                    
                </h3>
                
                {$contentBranch}
                
            </li>
            
            {/foreach}
            
            </ol>
        </div>
        {/set-block}
    {/if}

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
        {if and( is_set( $attribute_content.import_ez_contents ), is_set( $attribute_content.have_contents ) ) }
            {if $errorTree|count()|gt( 0 )}
                <a href="#contents" title="{"%number errors found."|i18n('extension/openmagazine', '', hash( '%number', $errorTree|count() ) )}">
                    <span class="error"><strong>{"%number errors found."|i18n('extension/openmagazine', '', hash( '%number', $errorTree|count() ) )}</strong></span>
                </a>
            {else}
                <span class="ok"><strong>{'No errors found'|i18n('extension/openmagazine')}</strong></span>
            {/if}
        {/if}        
        </p>
    {/if} 

    {foreach $attribute_content.svg_files as $id => $source}
        <div id="spread-container-{$id}" class="spread-container" style="text-align:center; margin:0 auto 10px; width:{$attribute_content.spreads.$id.width}px; height:{$attribute_content.spreads.$id.height}px;"></div>
    {/foreach} 
    
    {if is_set( $attribute_content.import_ez_contents ) }

        {if eq( $#node.node_id, $attribute_content.source_node_id )}
            {def $contentParent = $#node}
        {else}
            {def $contentParent = fetch( 'content', 'node', hash( 'node_id', $attribute_content.source_node_id ) )}
        {/if}    
        {if is_set( $contentParent )}
            <h2 id="contents">    
                {'Content imported from'|i18n( 'extension/openmagazine' )} <a href={$contentParent.url_alias|ezurl()} title="node#{$contentParent.node_id}">{$contentParent.name|wash()}</a>
                {if is_set( $attribute_content.have_contents )} <small style="cursor:pointer;">[{'expand all'|i18n( 'extension/openmagazine' )}]</small>{/if}
            </h2>
        {/if}
        
        {if is_set( $attribute_content.have_contents )|not()}
        
            {if and( is_set( $contentParent ), $contentParent.children )}
            <div class="warning">
                <p>{'Not found any content that matches the priorities of Idml layout'|i18n( 'extension/openmagazine' )}.
                <a href={concat( "openmagazine/sort/", $contentParent.node_id, "/(RedirectURL)/", $#node.url_alias )|ezurl()} title="{'Sort source contents priority'|i18n( 'extension/openmagazine' )}">{'Sort source contents priority'|i18n( 'extension/openmagazine' )}</a></p>
            </div>
            {/if}
            
        {else}
        
            {$contentTree}
        
        {/if}
    {/if}
    
{/if}