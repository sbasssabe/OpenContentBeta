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
                        if ( data.content !== null )
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
    });
    </script>
    {/literal}   

    {if is_set( $attribute_content.idml_info ) }
        <p class="idml-info text-center">
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
        <div id="spread-container-{$id}" class="spread-container" style="text-align:center; margin:0 auto 10px; width:{$attribute_content.spreads.$id.width}px; height:{$attribute_content.spreads.$id.height}px;"></div>
    {/foreach} 
    
    
{/if}