{set scope=global persistent_variable=hash('left_menu', false(),
                                           'extra_menu', false())}

{ezscript_require( array( 'ezjsc::jquery' ) )}

{literal}
<script type="text/javascript">

$(function() {
    
    var toggledetail = function(){
        $(this).parent().next().toggle();
        if ($(this).parent().find('.toggledetail').html() == '[+]')
            $(this).parent().find('.toggledetail').html( '[-]' );
        else
            $(this).parent().find('.toggledetail').html( '[+]' );
    }
    $('ol.content_matched h3 span').bind( 'click', toggledetail );
     
});
</script>
{/literal}

{ezcss_require( array( 'ezidml.css' ) )}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="content-view-full">
    <div class="class-folder">

        <div class="attribute-header">
            <h1>{attribute_view_gui attribute=$node.data_map.name}</h1>
        </div>

        {if $node.object.data_map.description.has_content}
            <div class="attribute-long">
                {attribute_view_gui attribute=$node.data_map.description}
            </div>
        {/if}

        {def $page_limit = 100
             $classes = array( 'magazine_section' )
             $children = array()
             $children_count = ''}

        {set $children_count=fetch_alias( 'children_count', hash( 'parent_node_id', $node.node_id,
                                                                  'class_filter_type', 'include',
                                                                  'class_filter_array', $classes ) )}

        <div class="content-view-children">
            {if $children_count}
                {foreach fetch_alias( 'children', hash( 'parent_node_id', $node.node_id,
                                                        'offset', $view_parameters.offset,
                                                        'sort_by', $node.sort_array,
                                                        'class_filter_type', 'include',
                                                        'class_filter_array', $classes,
                                                        'limit', $page_limit ) ) as $child }
                    {node_view_gui view='line' content_node=$child}
                {/foreach}
            {/if}
        </div>
        
        {include name=navigator
                 uri='design:navigator/google.tpl'
                 page_uri=$node.url_alias
                 item_count=$children_count
                 view_parameters=$view_parameters
                 item_limit=$page_limit}

    </div>
</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>